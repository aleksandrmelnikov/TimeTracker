<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Api\ApiTag;
use App\Api\ApiTimestamp;
use App\Entity\Tag;
use App\Entity\Timestamp;
use App\Entity\TimestampTag;
use App\Form\Model\TimestampEditModel;
use App\Form\TimestampEditFormType;
use App\Repository\TagRepository;
use App\Repository\TimestampRepository;
use App\Repository\TimestampTagRepository;
use Knp\Bundle\TimeBundle\DateTimeFormatter;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TimestampController extends BaseController
{
    const codeTagNotAssociated = 'tag_not_associated';

    #[Route('/timestamp', name: 'timestamp_index')]
    public function index(
        Request $request,
        TimestampRepository $timestampRepository,
        PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        // TODO add filter
        $queryBuilder = $timestampRepository->findByUserQueryBuilder($this->getUser());
        $queryBuilder = $timestampRepository->preloadTags($queryBuilder);

        $pagination = $this->populatePaginationData($request, $paginator, $queryBuilder, [
            'sort' => 'timestamp.createdAt',
            'direction' => 'desc'
        ]);

        return $this->render('timestamp/index.html.twig', [
            'pagination' => $pagination
        ]);
    }

    #[Route('/timestamp/create', name: 'timestamp_create')]
    public function create(Request $request) {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $timestamp = new Timestamp($this->getUser());

        $this->getDoctrine()->getManager()->persist($timestamp);
        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('timestamp_view', ['id' => $timestamp->getIdString()]);
    }

    #[Route('/timestamp/{id}/view', name: 'timestamp_view')]
    public function view(
        Request $request,
        TimestampRepository $timestampRepository,
        TimestampTagRepository $timestampTagRepository,
        string $id) {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        /** @var Timestamp|null $timestamp */
        $timestamp = $timestampRepository->find($id);

        if (is_null($timestamp)) {
            throw $this->createNotFoundException();
        }

        if (!$this->getUser()->equalIds($timestamp->getCreatedBy())) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(TimestampEditFormType::class, TimestampEditModel::fromEntity($timestamp), [
            'timezone' => $this->getUser()->getTimezone(),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var TimestampEditModel $data */
            $data = $form->getData();

            $timestamp->setCreatedAt($data->getCreatedAt());

            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', 'Updated timestamp');
        }

        $timestampTags = $timestampTagRepository->findBy(['timestamp' => $timestamp]);
        $apiTags = array_map(
            fn($timestampTag) => ApiTag::fromEntity($timestampTag->getTag()),
            $timestampTags
        );

        return $this->render('timestamp/view.html.twig', [
            'form' => $form->createView(),
            'timestamp' => $timestamp,
            'tags' => $apiTags
        ]);
    }

    #[Route('/timestamp/{id}/delete', name: 'timestamp_delete')]
    public function remove(
        Request $request,
        TimestampRepository $timestampRepository,
        TimestampTagRepository $timestampTagRepository,
        string $id) {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        /** @var Timestamp|null $timestamp */
        $timestamp = $timestampRepository->find($id);

        if (is_null($timestamp)) {
            throw $this->createNotFoundException();
        }

        if (!$this->getUser()->equalIds($timestamp->getCreatedBy())) {
            throw $this->createAccessDeniedException();
        }

        $timestampTagRepository->removeForTimestamp($timestamp);
        $manager = $this->getDoctrine()->getManager();
        $manager->remove($timestamp);
        $manager->flush();

        $this->addFlash('success', 'Timestamp was removed');

        return $this->redirectToRoute('timestamp_index');
    }

    #[Route('/json/timestamp/{id}/repeat', name: 'timestamp_json_repeat', methods: ['POST'])]
    public function jsonRepeat(
        Request $request,
        DateTimeFormatter $dateTimeFormatter,
        TimestampRepository $timestampRepository,
        string $id)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $timestamp = $timestampRepository->find($id);
        if (is_null($timestamp)) {
            throw $this->createNotFoundException();
        }

        $manager = $this->getDoctrine()->getManager();
        // Get the tags and assign it to the new timestamp
        $newTimestamp = new Timestamp($this->getUser());
        $manager->persist($newTimestamp);

        foreach($timestamp->getTimestampTags() as $timestampTag) {
            $newTimestampTag = new TimestampTag($newTimestamp, $timestampTag->getTag());
            $newTimestamp->addTimestampTag($newTimestampTag);
            $manager->persist($newTimestampTag);
        }

        $manager->flush();

        $apiTimestamp = ApiTimestamp::fromEntity(
            $dateTimeFormatter,
            $newTimestamp,
            $this->getUser(),
            $this->now()
        );

        return $this->json($apiTimestamp, Response::HTTP_CREATED);
    }

    #[Route('/json/timestamp/{id}/tag', name: 'timestamp_json_tag_create', methods: ['POST'])]
    public function jsonAddTag(
        Request $request,
        TimestampRepository $timestampRepository,
        TagRepository $tagRepository,
        TimestampTagRepository $timestampTagRepository,
        string $id) {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $timestamp = $timestampRepository->find($id);
        if (is_null($timestamp)) {
            throw $this->createNotFoundException();
        }

        $data = json_decode($request->getContent(), true);
        if (!array_key_exists('tagName', $data)) {
            $problem = new ApiProblem(Response::HTTP_BAD_REQUEST, ApiProblem::TYPE_VALIDATION_ERROR);
            $problem->set('errors', [
                'message' => 'Missing value',
                'property' => 'tagName'
            ]);

            throw new ApiProblemException(
                $problem
            );
        }

        $tagName = $data['tagName'];

        $tag = $tagRepository->findOneBy(['name' => $tagName]);
        if (is_null($tag)) {
            $tag = new Tag($tagName);
            $this->getDoctrine()->getManager()->persist($tag);
        }

        $exitingLink = $timestampTagRepository->findOneBy([
                                                              'timestamp' => $timestamp,
                                                              'tag' => $tag
                                                          ]);

        if (!is_null($exitingLink)) {
            return $this->json([], Response::HTTP_CONFLICT);
        }

        $timestampTag = new TimestampTag($timestamp, $tag);
        $manager = $this->getDoctrine()->getManager();
        $manager->persist($timestampTag);
        $manager->flush();

        $apiTag = ApiTag::fromEntity($tag);

        return $this->json($apiTag, Response::HTTP_CREATED);
    }

    #[Route('/json/timestamp/{id}/tag/{tagName}', name: 'timestamp_json_tag_delete', methods: ['DELETE'])]
    public function jsonDeleteTag(
        Request $request,
        TimestampRepository $timestampRepository,
        TagRepository $tagRepository,
        TimestampTagRepository $timestampTagRepository,
        string $id,
        string $tagName) {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $tag = $tagRepository->findOneBy(['name' => $tagName]);
        if (is_null($tag)) {
            throw $this->createNotFoundException("tag '$tagName'");
        }

        $timestamp = $timestampRepository->find($id);
        if (is_null($timestamp)) {
            throw $this->createNotFoundException('Timestamp');
        }

        $exitingLink = $timestampTagRepository->findOneBy([
                                                              'timestamp' => $timestamp,
                                                              'tag' => $tag
                                                          ]);

        if (is_null($exitingLink)) {
            throw new ApiProblemException(
                ApiProblem::invalidAction(
                    self::codeTagNotAssociated,
                    "Tag '$tagName' is not associated to this timestamp")
            );
        }

        $manager = $this->getDoctrine()->getManager();
        $manager->remove($exitingLink);
        $manager->flush();

        return $this->jsonOk();
    }

    #[Route('/json/timestamp/{id}/tags', name: 'timestamp_json_tags')]
    public function jsonTags(
        Request $request,
        TimestampRepository $timestampRepository,
        TimestampTagRepository $timestampTagRepository,
        string $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        /** @var Timestamp|null $timestamp */
        $timestamp = $timestampRepository->find($id);
        if (is_null($timestamp)) {
            return $this->json([], Response::HTTP_NOT_FOUND);
        }

        $timestampTags = $timestampTagRepository->findBy(['timestamp' => $timestamp]);

        $apiTags = array_map(
            fn($timestampTag) => ApiTag::fromEntity($timestampTag->getTag()),
            $timestampTags
        );

        return $this->json($apiTags);
    }
}

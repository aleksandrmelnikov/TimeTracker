<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TagLinkRepository;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

/**
 * @ORM\Entity(repositoryClass=TagLinkRepository::class)
 */
class TagLink
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=TimeEntry::class, inversedBy="tagLinks")
     * @ORM\JoinColumn(nullable=true)
     * @var TimeEntry|null
     */
    private $timeEntry;

    /**
     * @ORM\ManyToOne(targetEntity=Timestamp::class, inversedBy="tagLinks")
     * @ORM\JoinColumn(nullable=true)
     * @var Timestamp|null
     */
    private $timestamp;

    /**
     * @ORM\ManyToOne(targetEntity=Task::class, inversedBy="tagLinks")
     * @ORM\JoinColumn(nullable=true)
     * @var Task|null
     */
    private $task;

    /**
     * @ORM\ManyToOne(targetEntity=Tag::class)
     * @ORM\JoinColumn(nullable=false)
     * @var Tag
     */
    private $tag;

    /**
     * @ORM\ManyToOne(targetEntity=Statistic::class)
     * @ORM\JoinColumn(nullable=true)
     * @var Statistic
     */
    private $statistic;

    public function __construct(mixed $resource, Tag $tag)
    {
        $this->tag = $tag;

        if ($resource instanceof TimeEntry) {
            $this->timeEntry = $resource;
        } elseif ($resource instanceof Timestamp) {
            $this->timestamp = $resource;
        } elseif ($resource instanceof Task) {
            $this->task = $resource;
        } else if ($resource instanceof Statistic) {
            $this->statistic = $resource;
        } else {
            throw new InvalidArgumentException("Resource for TagLink not supported");
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTimeEntry(): ?TimeEntry
    {
        return $this->timeEntry;
    }

    public function getTimestamp(): ?Timestamp
    {
        return $this->timestamp;
    }

    public function getTask(): ?Task
    {
        return$this->task;
    }

    public function getStatistic(): ?Statistic
    {
        return $this->statistic;
    }

    public function getTag(): Tag
    {
        return $this->tag;
    }
}

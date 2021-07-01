<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TagRepository;
use App\Traits\AssignableToUserTrait;
use App\Traits\CreateTimestampableTrait;
use App\Traits\UUIDTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Entity(repositoryClass=TagRepository::class)
 */
class Tag
{
    use UUIDTrait;
    use CreateTimestampableTrait;
    use AssignableToUserTrait;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     * @var string
     */
    private $canonicalName;

    /**
     * @ORM\Column(type="string", length=7)
     * @var string
     *
     * Hex color string, includes #. e.g. #FF0000
     */
    private $color;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false)
     * @var User
     */
    private $assignedTo;

    /**
     * @ORM\OneToMany(targetEntity=TagLink::class, mappedBy="tag", orphanRemoval=true)
     * @var TagLink[]|Collection
     */
    private $tagLinks;

    public function __construct(
        User $assignedTo,
        string $name,
        string $color = '#5d5d5d',
        DateTime $createdAt = null
    ) {
        $this->id = Uuid::uuid4();
        $this->assignTo($assignedTo);
        $this->setName($name);
        $this->color = $color;
        $this->markCreated($createdAt);
    }

    private function canonicalizeName(string $name): string
    {
        return trim(strtolower($name));
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCanonicalName(): string
    {
        return $this->canonicalName;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        $this->canonicalName = $this->canonicalizeName($name);

        return $this;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;
        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }
}

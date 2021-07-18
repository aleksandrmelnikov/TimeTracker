<?php

declare(strict_types=1);

namespace App\Form\Model;

class TaskListFilterModel
{
    private bool $showCompleted;
    private ?string $content;
    private ?string $tags;
    private ?string $parentTask;

    public function __construct()
    {
        $this->showCompleted = false;
        $this->content = null;
        $this->tags = null;
        $this->parentTask = null;
    }

    public function getShowCompleted(): bool
    {
        return $this->showCompleted;
    }

    public function setShowCompleted(bool $showCompleted): self
    {
        $this->showCompleted = $showCompleted;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function hasContent(): bool
    {
        return !is_null($this->content);
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getTags(): string
    {
        return $this->tags;
    }

    public function hasTags(): bool
    {
        return !is_null($this->tags) && $this->tags !== '';
    }

    /**
     * @return string[]
     */
    public function getTagsArray(): array
    {
        if (is_null($this->tags) || $this->tags === '') {
            return [];
        }

        $results = explode(',', $this->tags);

        $results = array_map(
            fn ($tagRaw) => str_replace(' ', '', $tagRaw),
            $results
        );

        return $results;
    }

    public function setTags(?string $tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    public function getParentTask(): ?string
    {
        return $this->parentTask;
    }

    public function hasParentTask(): bool
    {
        return !is_null($this->parentTask);
    }

    public function setParentTask(?string $parentTask): self
    {
        $this->parentTask = $parentTask;
        return $this;
    }
}

<?php

declare(strict_types=1);

namespace App\Database\Entities;

abstract class AbstractEntity
{
    /** @var array **/
    protected $props;

    public function __construct(array $props)
    {
        $this->props = $props;
    }

    public function getKeys(): array
    {
        return [
            'id' => $this->props['id'],
        ];
    }

    /**
     * Used after adding an entity, when autoincrement id is available.
     **/
    public function setId(int $id): void
    {
        $this->props['id'] = $id;
    }

    public function toArray(): array
    {
        return $this->props;
    }
}

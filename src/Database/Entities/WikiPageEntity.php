<?php

declare(strict_types=1);

namespace App\Database\Entities;

class WikiPageEntity extends AbstractEntity
{
    public function getTitle(): string
    {
        return $this->props['title'];
    }

    public function getText(): string
    {
        return $this->props['text'];
    }
}

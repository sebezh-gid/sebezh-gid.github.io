<?php

declare(strict_types=1);

namespace App\Database;

use App\Database\Entities\AbstractEntity;
use App\Database\Entities\WikiPageEntity;

interface DatabaseInterface
{
    public function add(AbstractEntity $entity): void;

    public function delete(AbstractEntity $entity): void;

    public function getWikiPage(string $name): WikiPageEntity;

    public function update(AbstractEntity $entity): void;
}

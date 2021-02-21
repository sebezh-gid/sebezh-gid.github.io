<?php

declare(strict_types=1);

namespace App\Wiki;

use App\Database\DatabaseInterface;
use App\Database\Entities\WikiPageEntity;

class Wiki
{
    /** @var DatabaseInterface **/
    protected $db;

    public function __construct(
        DatabaseInterface $db
    ) {
        $this->db = $db;
    }

    public function getPageByName(string $pageName): WikiPageEntity
    {
        $page = $this->db->getWikiPage($pageName);
        return $page;
    }
}

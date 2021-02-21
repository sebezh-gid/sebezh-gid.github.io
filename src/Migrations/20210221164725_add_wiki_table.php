<?php

declare(strict_types=1);

namespace App\Migrations;

use Phinx\Migration\AbstractMigration;

final class AddWikiTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('wiki_pages', ['signed' => false])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('updated', 'datetime', ['null' => false])
            ->addColumn('key', 'string', ['limit' => 32, 'null' => false])
            ->addColumn('text', 'text')
            ->addIndex(['created'])
            ->addIndex(['updated'])
            ->addIndex(['key'], ['unique' => true])
            ->save();
    }

    public function down(): void
    {
        $this->table('wiki_pages')->drop()->save();
    }
}

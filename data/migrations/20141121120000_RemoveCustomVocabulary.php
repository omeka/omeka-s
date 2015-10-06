<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class RemoveCustomVocabulary extends AbstractMigration
{
    public function up()
    {
        $this->getConnection()->delete('vocabulary', ['prefix' => 'omeka']);
    }
}

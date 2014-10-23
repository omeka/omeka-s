<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class ResourcePropertyAssignmentSet extends AbstractMigration
{
    public function up()
    {
        $resourceTable = $this->getDbHelper()
            ->getTableNameForEntity('Omeka\Model\Entity\Resource');
        $statements = "
        ALTER TABLE $resourceTable ADD property_assignment_set_id INT DEFAULT NULL;
        ALTER TABLE $resourceTable ADD CONSTRAINT FK_CB5438CAD1169F72 FOREIGN KEY (property_assignment_set_id) REFERENCES omeka_property_assignment_set (id);
        CREATE INDEX IDX_CB5438CAD1169F72 ON $resourceTable (property_assignment_set_id);";
        $this->getDbHelper()->execute($statements);
    }
}

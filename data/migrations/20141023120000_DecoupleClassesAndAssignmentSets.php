<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class DecoupleClassesAndAssignmentSets extends AbstractMigration
{
    public function up()
    {
        $resourceClassTable = $this->getDbHelper()
            ->getTableNameForEntity('Omeka\Model\Entity\ResourceClass');
        $propertyAssignmentSetTable = $this->getDbHelper()
            ->getTableNameForEntity('Omeka\Model\Entity\PropertyAssignmentSet');
        $statements = "
        ALTER TABLE $propertyAssignmentSetTable DROP FOREIGN KEY FK_D57D24E0448CC1BD;
        DROP INDEX resource_class_label ON $propertyAssignmentSetTable;
        DROP INDEX IDX_D57D24E0448CC1BD ON $propertyAssignmentSetTable;
        ALTER TABLE $propertyAssignmentSetTable DROP resource_class_id;";
        $this->getDbHelper()->execute($statements);
    }
}

<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class ResourceTemplate extends AbstractMigration
{
    public function up()
    {
        $db = $this->getDbHelper();

        $resourceTemplate = $db->getTableNameForEntity('Omeka\Model\Entity\ResourceTemplate');
        $resourceTemplateProperty = $db->getTableNameForEntity('Omeka\Model\Entity\ResourceTemplateProperty');
        $user = $db->getTableNameForEntity('Omeka\Model\Entity\User');
        $property = $db->getTableNameForEntity('Omeka\Model\Entity\Property');
        $resource = $db->getTableNameForEntity('Omeka\Model\Entity\Resource');
        $propertyAssignment = $db->getTableNameForBaseTable('property_assignment');
        $propertyAssignmentSet = $db->getTableNameForBaseTable('property_assignment_set');

        $statements = "
CREATE TABLE $resourceTemplate (
    id INT AUTO_INCREMENT NOT NULL,
    owner_id INT DEFAULT NULL,
    `label` VARCHAR(255) NOT NULL,
    INDEX IDX_6ED4B82E7E3C61F9 (owner_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;

CREATE TABLE $resourceTemplateProperty (
    id INT AUTO_INCREMENT NOT NULL,
    resource_template_id INT NOT NULL,
    property_id INT NOT NULL,
    alternate_label VARCHAR(255) DEFAULT NULL,
    alternate_comment LONGTEXT DEFAULT NULL,
    INDEX IDX_876084E116131EA (resource_template_id),
    INDEX IDX_876084E1549213EC (property_id), PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;

ALTER TABLE $resourceTemplate
    ADD CONSTRAINT FK_6ED4B82E7E3C61F9 FOREIGN KEY (owner_id) REFERENCES $user (id);

ALTER TABLE $resourceTemplateProperty
    ADD CONSTRAINT FK_876084E116131EA FOREIGN KEY (resource_template_id) REFERENCES $resourceTemplate (id),
    ADD CONSTRAINT FK_876084E1549213EC FOREIGN KEY (property_id) REFERENCES $property (id);

ALTER TABLE $resource DROP FOREIGN KEY FK_CB5438CAD1169F72,
    DROP INDEX IDX_CB5438CAD1169F72,
    CHANGE property_assignment_set_id resource_template_id INT DEFAULT NULL,
    ADD CONSTRAINT FK_CB5438CA16131EA FOREIGN KEY (resource_template_id) REFERENCES $resourceTemplate (id),
    ADD INDEX IDX_CB5438CA16131EA (resource_template_id);

DROP TABLE $propertyAssignment, $propertyAssignmentSet;";

        $this->getDbHelper()->execute($statements);
    }
}

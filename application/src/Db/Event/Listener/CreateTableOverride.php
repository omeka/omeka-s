<?php
namespace Omeka\Db\Event\Listener;

use Doctrine\DBAL\Event\SchemaCreateTableEventArgs;

/**
 * Replace Doctrine's auto-generated CREATE TABLE definitions with our own
 */
class CreateTableOverride
{
    /**
     * Replace the "value" table definition with a custom one defining the indexes we
     * want it have (which are not describable within Doctrine).
     *
     * @param SchemaCreateTableEventArgs $event
     */
    public function onSchemaCreateTable(SchemaCreateTableEventArgs $event)
    {
        if ($event->getTable()->getName() === 'value') {
            $sql = <<<SQL
CREATE TABLE value (
    id INT AUTO_INCREMENT NOT NULL,
    resource_id INT NOT NULL,
    property_id INT NOT NULL,
    value_resource_id INT DEFAULT NULL,
    type VARCHAR(255) NOT NULL,
    lang VARCHAR(255) DEFAULT NULL,
    value LONGTEXT DEFAULT NULL,
    uri LONGTEXT DEFAULT NULL,
    INDEX IDX_1D77583489329D25 (resource_id),
    INDEX IDX_1D775834549213EC (property_id),
    INDEX IDX_1D7758344BC72506 (value_resource_id),
    INDEX (value(190)),
    INDEX (uri(190)),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
SQL;
            $event->addSql($sql);
            $event->preventDefault();
        }
    }
}

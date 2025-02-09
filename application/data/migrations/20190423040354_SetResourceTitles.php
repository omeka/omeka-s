<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class SetResourceTitles implements MigrationInterface
{
    public function up(Connection $conn)
    {
        // Get the dcterms:title property.
        $sql = '
        SELECT p.id
        FROM property p
        INNER JOIN vocabulary v ON v.id = p.vocabulary_id
        AND p.local_name = "title"
        AND v.namespace_uri = "http://purl.org/dc/terms/"';
        $propertyId = $conn->fetchColumn($sql, [], 0);

        // Set resource titles to the first dcterms:title value, if any.
        $sql = '
        UPDATE resource
        SET resource.title = (
            SELECT v.value 
            FROM value v
            WHERE v.resource_id = resource.id
            AND v.property_id = ?
            AND v.value IS NOT NULL
            AND v.value != ""
            ORDER BY v.id ASC
            LIMIT 1
        )';
        $conn->executeUpdate($sql, [$propertyId]);
    }
}

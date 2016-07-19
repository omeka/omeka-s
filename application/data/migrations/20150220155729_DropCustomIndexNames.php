<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class DropCustomIndexNames implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('DROP INDEX vocabulary_local_name ON resource_class;');
        $conn->query('CREATE UNIQUE INDEX UNIQ_C6F063ADAD0E05F6623C14D5 ON resource_class (vocabulary_id, local_name);');
        $conn->query('DROP INDEX vocabulary_local_name ON property;');
        $conn->query('CREATE UNIQUE INDEX UNIQ_8BF21CDEAD0E05F6623C14D5 ON property (vocabulary_id, local_name);');
        $conn->query('DROP INDEX site_user ON site_permission;');
        $conn->query('CREATE UNIQUE INDEX UNIQ_C0401D6FF6BD1646A76ED395 ON site_permission (site_id, user_id);');
        $conn->query('DROP INDEX site_slug ON site_page;');
        $conn->query('CREATE UNIQUE INDEX UNIQ_2F900BD9F6BD1646989D9B62 ON site_page (site_id, slug);');
    }
}

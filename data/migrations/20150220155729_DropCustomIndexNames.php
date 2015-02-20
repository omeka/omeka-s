<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class DropCustomIndexNames extends AbstractMigration
{
    public function up()
    {
        $connection = $this->getConnection();
        $connection->query('DROP INDEX vocabulary_local_name ON resource_class;');
        $connection->query('CREATE UNIQUE INDEX UNIQ_C6F063ADAD0E05F6623C14D5 ON resource_class (vocabulary_id, local_name);');
        $connection->query('DROP INDEX vocabulary_local_name ON property;');
        $connection->query('CREATE UNIQUE INDEX UNIQ_8BF21CDEAD0E05F6623C14D5 ON property (vocabulary_id, local_name);');
        $connection->query('DROP INDEX site_user ON site_permission;');
        $connection->query('CREATE UNIQUE INDEX UNIQ_C0401D6FF6BD1646A76ED395 ON site_permission (site_id, user_id);');
        $connection->query('DROP INDEX site_slug ON site_page;');
        $connection->query('CREATE UNIQUE INDEX UNIQ_2F900BD9F6BD1646989D9B62 ON site_page (site_id, slug);');
    }
}

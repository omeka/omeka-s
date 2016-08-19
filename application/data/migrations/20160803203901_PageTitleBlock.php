<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class PageTitleBlock implements MigrationInterface
{
    public function up(Connection $conn)
    {
        // Add the pageTitle block to the top of every existing page.
        $conn->exec('UPDATE site_page_block SET position = position + 1;');
        $stmt = $conn->query('SELECT DISTINCT(id) FROM site_page');
        while ($row = $stmt->fetch()) {
            $conn->insert('site_page_block', [
                'page_id' => $row['id'],
                'layout' => 'pagetitle',
                'data' => '[]',
                'position' => 1,
            ]);
        }
    }
}

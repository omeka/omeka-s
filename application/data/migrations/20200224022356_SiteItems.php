<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\ConstructedMigrationInterface;
use Omeka\Job\Dispatcher;
use Laminas\ServiceManager\ServiceLocatorInterface;

class SiteItems implements ConstructedMigrationInterface
{
    /**
     * @var Dispatcher
     */
    private $dispatcher;

    public static function create(ServiceLocatorInterface $services)
    {
        return new self($services->get('Omeka\Job\Dispatcher'));
    }

    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function up(Connection $conn)
    {
        $conn->exec('CREATE TABLE item_site (item_id INT NOT NULL, site_id INT NOT NULL, INDEX IDX_A1734D1F126F525E (item_id), INDEX IDX_A1734D1FF6BD1646 (site_id), PRIMARY KEY(item_id, site_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;');
        $conn->exec('ALTER TABLE item_site ADD CONSTRAINT FK_A1734D1F126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE CASCADE;');
        $conn->exec('ALTER TABLE item_site ADD CONSTRAINT FK_A1734D1FF6BD1646 FOREIGN KEY (site_id) REFERENCES site (id) ON DELETE CASCADE;');

        $sites = [];
        $stmt = $conn->query('SELECT id, item_pool FROM site');
        while ($row = $stmt->fetch()) {
            $sites[$row['id']] = json_decode($row['item_pool'], true);
        }
        $this->dispatcher->dispatch('Omeka\Job\UpdateSiteItems', [
            'sites' => $sites,
            'action' => 'add',
        ]);
    }
}

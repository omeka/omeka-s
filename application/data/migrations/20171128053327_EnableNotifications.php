<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\ConstructedMigrationInterface;
use Omeka\Settings\Settings;
use Zend\ServiceManager\ServiceLocatorInterface;

class EnableNotifications implements ConstructedMigrationInterface
{
    /**
     * @var Settings
     */
    private $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function up(Connection $conn)
    {
        $this->settings->set('version_notifications', '1');
    }

    public static function create(ServiceLocatorInterface $services)
    {
        return new self($services->get('Omeka\Settings'));
    }
}

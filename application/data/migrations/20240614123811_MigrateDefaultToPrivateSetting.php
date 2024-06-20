<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Omeka\Db\Migration\ConstructedMigrationInterface;

class MigrateDefaultToPrivateSetting implements ConstructedMigrationInterface
{
    private $settings;

    public static function create(ServiceLocatorInterface $services)
    {
        return new self($services->get('Omeka\Settings'));
    }

    public function __construct($settings)
    {
        $this->settings = $settings;
    }

    public function up(Connection $conn)
    {
        // Migrate from the general "default_to_private" setting to granular settings.
        $defaultToPrivate = (bool) $this->settings->get('default_to_private', false);
        $this->settings->set('default_to_private_items', $defaultToPrivate);
        $this->settings->set('default_to_private_item_sets', $defaultToPrivate);
        $this->settings->set('default_to_private_sites', $defaultToPrivate);
        $this->settings->set('default_to_private_site_pages', $defaultToPrivate);
        $this->settings->delete('default_to_private');
    }
}

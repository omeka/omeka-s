<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\ConstructedMigrationInterface;
use Omeka\Form\SettingForm;
use Omeka\Settings\Settings;
use Zend\ServiceManager\ServiceLocatorInterface;

class AddFileValidation implements ConstructedMigrationInterface
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
        $mediaTypes = SettingForm::MEDIA_TYPE_WHITELIST;
        $this->settings->set('media_type_whitelist', $mediaTypes);
        $extensions = SettingForm::EXTENSION_WHITELIST;
        $this->settings->set('extension_whitelist', $extensions);
    }

    public static function create(ServiceLocatorInterface $serviceLocator)
    {
        return new self($serviceLocator->get('Omeka\Settings'));
    }
}

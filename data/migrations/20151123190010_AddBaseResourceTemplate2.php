<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Api\Manager as ApiManager;
use Omeka\Db\Migration\ConstructedMigrationInterface;
use Omeka\Installation\Task\InstallDefaultTemplatesTask;
use Zend\ServiceManager\ServiceLocatorInterface;

class AddBaseResourceTemplate2 implements ConstructedMigrationInterface
{
    /**
     * @var ApiManager
     */
    private $api;

    public function __construct(ApiManager $api)
    {
        $this->api = $api;
    }

    public function up(Connection $conn)
    {
        $task = new InstallDefaultTemplatesTask;
        $task->setApi($this->api);
        $task->installTemplate('Base Resource');
    }

    public static function create(ServiceLocatorInterface $serviceLocator)
    {
        return new self($serviceLocator->get('Omeka\ApiManager'));
    }
}

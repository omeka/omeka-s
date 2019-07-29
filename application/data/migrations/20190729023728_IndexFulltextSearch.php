<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\ConstructedMigrationInterface;
use Omeka\Job\Dispatcher;
use Zend\ServiceManager\ServiceLocatorInterface;

class IndexFulltextSearch implements ConstructedMigrationInterface
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
        $this->dispatcher->dispatch('Omeka\Job\IndexFulltextSearch');
    }
}

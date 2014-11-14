<?php
namespace OmekaTest\Db\Migration;

use Omeka\Db\Migration\MigrationInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class MockMigration implements MigrationInterface
{
    private $sl;

    public function up()
    {}

    public function down()
    {}

    public function setServiceLocator(ServiceLocatorInterface $sl)
    {
        $this->sl = $sl;
    }

    public function getServiceLocator()
    {
        return $this->sl;
    }
}

<?php
namespace OmekaTest\Api\Adapter;

use Omeka\Api\Adapter\Manager;
use Omeka\Test\TestCase;

class ManagerTest extends TestCase
{
    protected $manager;

    public function setUp()
    {
        $this->manager = new Manager($this->getMockForAbstractClass('Interop\Container\ContainerInterface'));
    }

    public function testValidateRequiresAdapterInterface()
    {
        $this->setExpectedException('Zend\ServiceManager\Exception\InvalidServiceException');
        $this->manager->validate(new \stdClass);
    }
}

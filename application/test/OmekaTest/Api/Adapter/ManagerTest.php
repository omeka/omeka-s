<?php
namespace OmekaTest\Api\Adapter;

use Omeka\Api\Adapter\Manager;
use Omeka\Test\TestCase;

class ManagerTest extends TestCase
{
    protected $manager;

    public function setUp()
    {
        $this->manager = new Manager;
    }

    public function testValidatePluginRequiresAdapterInterface()
    {
        $this->setExpectedException('Omeka\Api\Exception\InvalidAdapterException');
        $this->manager->validatePlugin(new \stdClass);
    }
}

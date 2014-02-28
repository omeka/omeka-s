<?php
namespace OmekaTest\Api;

use Omeka\Api\Adapter\Entity\ModuleAdapter;
use Omeka\Model\Entity\Module;

class ModuleAdapterTest extends \PHPUnit_Framework_TestCase
{
    protected $adapter;

    protected $data = array(
        'id' => 'TestModule',
        'is_active' => true,
    );

    public function setUp()
    {
        $this->adapter = new ModuleAdapter;
    }

    public function testGetEntityClass()
    {
        $this->assertEquals(
            'Omeka\Model\Entity\Module',
            $this->adapter->getEntityClass()
        );
    }

    public function testHydrate()
    {
        $entity = new Module;
        $this->adapter->hydrate($this->data, $entity);
        $this->assertEquals($this->data['id'], $entity->getId());
        $this->assertEquals($this->data['is_active'], $entity->getIsActive());
    }

    public function testExtract()
    {
        $entity = new Module;
        $entity->setId($this->data['id']);
        $entity->setIsActive($this->data['is_active']);
        $data = $this->adapter->extract($entity);
        $this->assertEquals($this->data['id'], $data['id']);
        $this->assertEquals($this->data['is_active'], $data['is_active']);
    }
}

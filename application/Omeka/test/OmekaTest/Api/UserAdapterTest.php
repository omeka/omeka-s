<?php
namespace OmekaTest\Api;

use Omeka\Api\Adapter\Entity\UserAdapter;
use Omeka\Model\Entity\User;
use Omeka\Test\MockBuilder;

class UserAdapterTest extends \PHPUnit_Framework_TestCase
{
    protected $adapter;

    protected $data = array(
        'username' => 'Test Username',
        'name' => 'Test Name',
        'email' => 'test@example.com',
    );

    public function setUp()
    {
        $this->adapter = new UserAdapter;
    }

    public function testHydrate()
    {
        $entity = new User;
        $this->adapter->hydrate($this->data, $entity);
        $this->assertEquals($this->data['username'], $entity->getUsername());
        $this->assertEquals($this->data['name'], $entity->getName());
        $this->assertEquals($this->data['email'], $entity->getEmail());
    }

    public function testExtract()
    {
        $entity = new User();
        foreach(array_keys($this->data) as $property) {
            $method = 'set' . ucfirst($property);
            $entity->$method($this->data[$property]);
        }
        $entity->setCreated();
        $data = $this->adapter->extract($entity);
        foreach(array_keys($this->data) as $property) {
            $this->assertEquals($this->data[$property], $data[$property]);
        }
        $this->assertInternalType('string', $data['created']);
    }
}
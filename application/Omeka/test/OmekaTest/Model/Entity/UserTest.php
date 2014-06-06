<?php
namespace OmekaTest\Model;

use DateTime;
use Omeka\Model\Entity\User;
use Omeka\Test\TestCase;

class UserTest extends TestCase
{
    protected $user;

    public function setUp()
    {
        $this->user = new User;
    }

    public function testInitialState()
    {
        $this->assertNull($this->user->getId());
        $this->assertNull($this->user->getUsername());
        $this->assertNull($this->user->getName());
        $this->assertNull($this->user->getCreated());
        $this->assertNull($this->user->getEmail());
    }

    public function testSetState()
    {
        $this->user->setUsername('username');
        $this->assertEquals('username', $this->user->getUsername());
        $this->user->setName('name');
        $this->assertEquals('name', $this->user->getName());
        $this->user->setEmail('test@example.com');
        $this->assertEquals('test@example.com', $this->user->getEmail());
    }

    public function testCreated()
    {
        $dateTime = new DateTime;
        $this->user->setCreated($dateTime);
        $this->assertSame($dateTime, $this->user->getCreated());
    }

    public function testSetsCreatedOnPersist()
    {
        $this->user->prePersist();
        $this->assertInstanceOf('DateTime', $this->user->getCreated());
    }
}

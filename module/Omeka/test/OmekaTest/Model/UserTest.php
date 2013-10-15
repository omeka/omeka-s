<?php
namespace OmekaTest\Model;

use Omeka\Model\Entity\User;

class UserTest extends \PHPUnit_Framework_TestCase
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
    }

    public function testSetState()
    {
        $this->user->setUsername('username');
        $this->assertEquals('username', $this->user->getUsername());
    }
}

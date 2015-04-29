<?php
namespace OmekaTest\Model;

use DateTime;
use Omeka\Entity\ApiKey;
use Omeka\Entity\User;
use Omeka\Test\TestCase;

class ApiKeyTest extends TestCase
{
    protected $key;

    public function setUp()
    {
        $this->key = new ApiKey;
    }

    public function testInitialState()
    {
        $this->assertNull($this->key->getId());
        $this->assertNull($this->key->getLabel());
        $this->assertNull($this->key->getOwner());
        $this->assertNull($this->key->getLastIp());
        $this->assertNull($this->key->getLastAccessed());
        $this->assertNull($this->key->getCreated());
        $this->assertFalse($this->key->verifyCredential('foo'));
    }

    public function testSetId()
    {
        $this->key->setId();
        $pattern = '/^[' . ApiKey::STRING_CHARLIST . ']{' . ApiKey::STRING_LENGTH . '}$/';
        $this->assertEquals(1, preg_match($pattern, $this->key->getId()));
    }

    public function testSetLabel()
    {
        $label = 'foo';
        $this->key->setLabel($label);
        $this->assertEquals($label, $this->key->getLabel());
    }

    public function testSetCredential()
    {
        $credential = $this->key->setCredential();
        $pattern = '/^[' . ApiKey::STRING_CHARLIST . ']{' . ApiKey::STRING_LENGTH . '}$/';
        $this->assertEquals(1, preg_match($pattern, $credential));
        $this->assertTrue($this->key->verifyCredential($credential));
        $this->assertFalse($this->key->verifyCredential('foo'));
    }

    public function testSetLastIp()
    {
        $ip = 'foo';
        $this->key->setLastIp($ip);
        $this->assertEquals($ip, $this->key->getLastIp());
    }

    public function testSetLastAccessed()
    {
        $dateTime = new DateTime;
        $this->key->setLastAccessed($dateTime);
        $this->assertSame($dateTime, $this->key->getLastAccessed());
    }

    public function testSetCreated()
    {
        $dateTime = new DateTime;
        $this->key->setCreated($dateTime);
        $this->assertSame($dateTime, $this->key->getCreated());
    }

    public function testSetsCreatedOnPersist()
    {
        $this->key->prePersist();
        $this->assertInstanceOf('DateTime', $this->key->getCreated());
    }

    public function testSetOwner()
    {
        $owner = new User;
        $this->key->setOwner($owner);
        $this->assertSame($owner, $this->key->getOwner());
    }
}

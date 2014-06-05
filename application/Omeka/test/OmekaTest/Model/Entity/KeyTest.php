<?php
namespace OmekaTest\Model;

use Omeka\Model\Entity\Key;
use Omeka\Test\TestCase;

class KeyTest extends TestCase
{
    protected $key;

    public function setUp()
    {
        $this->key = new Key;
    }

    public function testInitialState()
    {
        $this->assertNull($this->key->getId());
        $this->assertNull($this->key->getUser());
        $this->assertNull($this->key->getLastIp());
        $this->assertFalse($this->key->verifyCredential('foo'));
    }

    public function testIdentity()
    {
        $this->key->setId();
        $pattern = '/^[' . Key::STRING_CHARLIST . ']{' . Key::STRING_LENGTH . '}$/';
        $this->assertEquals(1, preg_match($pattern, $this->key->getId()));
    }

    public function testCredential()
    {
        $credential = $this->key->setCredential();
        $pattern = '/^[' . Key::STRING_CHARLIST . ']{' . Key::STRING_LENGTH . '}$/';
        $this->assertEquals(1, preg_match($pattern, $credential));
        $this->assertTrue($this->key->verifyCredential($credential));
        $this->assertFalse($this->key->verifyCredential('foo'));
    }

    public function testlastIp()
    {
        $ip = 'foo';
        $this->key->setLastIp($ip);
        $this->assertEquals($ip, $this->key->getLastIp());
    }
}

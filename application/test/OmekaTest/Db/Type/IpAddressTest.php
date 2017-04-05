<?php
namespace OmekaTest\Db\Type;

use Doctrine\DBAL\Types\Type;
use Omeka\Test\TestCase;

class IpAddressTest extends TestCase
{
    const TYPE_NAME = 'ip_address';

    protected $ipAddress;
    protected $platform;

    public function setUp()
    {
        // Type is a singleton.
        if (Type::hasType(self::TYPE_NAME)) {
            // Remove the type so it can be re-instantiated.
            Type::overrideType(self::TYPE_NAME, null);
        }
        Type::addType(self::TYPE_NAME, 'Omeka\Db\Type\IpAddress');

        $this->ipAddress = Type::getType(self::TYPE_NAME);
        $this->platform = $this->getMockForAbstractClass(
            'Doctrine\DBAL\Platforms\AbstractPlatform'
        );
    }

    public function testSqlDeclaration()
    {
        $sqlDeclaration = $this->ipAddress->getSqlDeclaration(
            [], $this->platform
        );
        $this->assertEquals('VARBINARY(16)', $sqlDeclaration);
    }

    public function testConvertToPHPValue()
    {
        $phpValue = $this->ipAddress->convertToPHPValue(null, $this->platform);
        $this->assertNull($phpValue);

        $ip = '127.0.0.1';
        $databaseValue = inet_pton($ip);
        $phpValue = $this->ipAddress->convertToPHPValue($databaseValue, $this->platform);
        $this->assertEquals($ip, $phpValue);
    }

    public function testConvertToDatabaseValue()
    {
        $databaseValue = $this->ipAddress->convertToDatabaseValue(null, $this->platform);
        $this->assertNull($databaseValue);

        $phpValue = '127.0.0.1';
        $databaseValue = $this->ipAddress->convertToDatabaseValue($phpValue, $this->platform);
        $this->assertEquals(inet_pton($phpValue), $databaseValue);
    }

    public function testGetName()
    {
        $this->assertEquals(self::TYPE_NAME, $this->ipAddress->getName());
    }

    public function testRequiresSQLCommentHint()
    {
        $this->assertTrue($this->ipAddress->requiresSQLCommentHint($this->platform));
    }
}

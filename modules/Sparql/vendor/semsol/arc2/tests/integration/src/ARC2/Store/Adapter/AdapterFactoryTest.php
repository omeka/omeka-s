<?php

namespace Tests\integration\src\ARC2\Store\Adapter;

use ARC2\Store\Adapter\AbstractAdapter;
use ARC2\Store\Adapter\AdapterFactory;
use Tests\ARC2_TestCase;

class AdapterFactoryTest extends ARC2_TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->fixture = new AdapterFactory();
    }

    /*
     * Tests for getInstanceFor
     */

    public function testGetInstanceFor()
    {
        // PDO (mysql)
        $instance = $this->fixture->getInstanceFor('pdo', ['db_pdo_protocol' => 'mysql']);
        $this->assertTrue($instance instanceof AbstractAdapter);
    }

    public function testGetInstanceForInvalidAdapterName()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unknown adapter name given. Currently supported are: pdo');

        $this->fixture->getInstanceFor('invalid');
    }

    public function testGetInstanceForInvalidPDOProtocol()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only "mysql" protocol is supported at the moment.');

        $instance = $this->fixture->getInstanceFor('pdo', ['db_pdo_protocol' => 'invalid']);
        $this->assertFalse($instance instanceof AbstractAdapter);
    }

    /*
     * Tests for getSupportedAdapters
     */

    public function testGetSupportedAdapters()
    {
        $this->assertEquals(['pdo'], $this->fixture->getSupportedAdapters());
    }
}

<?php
namespace OmekaTest\Api;

use Omeka\Api\Request;
use Omeka\Test\TestCase;

class RequestTest extends TestCase
{
    protected $validOperations = [
        'search','create','read','update','delete',
    ];

    public function testInitialState()
    {
        $request = new Request('search', 'foo');
        $this->assertEquals([], $request->getContent());
        $this->assertNull($request->getId());
    }

    public function testConstructorSetsProperties()
    {
        $request = new Request('search', 'foo');
        $this->assertEquals('search', $request->getOperation());
        $this->assertEquals('foo', $request->getResource());
    }

    public function testSetsAndGetsId()
    {
        $request = new Request('search', 'foo');
        $request->setId('foo');
        $this->assertEquals('foo', $request->getId());
    }

    public function testSetsAndGetsContent()
    {
        $request = new Request('search', 'foo');
        $this->assertEquals([], $request->getContent());
    }
}

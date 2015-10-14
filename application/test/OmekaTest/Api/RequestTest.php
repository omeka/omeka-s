<?php
namespace OmekaTest\Api;

use Omeka\Api\Request;
use Omeka\Test\TestCase;

class RequestTest extends TestCase
{
    protected $request;

    public function setUp()
    {
        $this->request = new Request;
    }

    protected $validOperations = [
        'search','create','read','update','delete',
    ];

    public function testInitialState()
    {
        $this->assertEquals([], $this->request->getContent());
        $this->assertNull($this->request->getOperation());
        $this->assertNull($this->request->getResource());
        $this->assertNull($this->request->getId());
    }

    public function testConstructorSetsProperties()
    {
        $request = new Request('search', 'foo');
        $this->assertEquals('search', $request->getOperation());
        $this->assertEquals('foo', $request->getResource());
    }

    public function testSetsAndGetsValidOperations()
    {
        foreach ($this->validOperations as $validOperation) {
            $this->request->setOperation($validOperation);
            $this->assertEquals($validOperation, $this->request->getOperation());
        }
    }

    public function testSetsAndGetsResource()
    {
        $this->request->setResource('foo');
        $this->assertEquals('foo', $this->request->getResource());
    }

    public function testSetsAndGetsId()
    {
        $this->request->setId('foo');
        $this->assertEquals('foo', $this->request->getId());
    }

    public function testSetsAndGetsContent()
    {
        $this->request->setContent('foo');
        $this->assertEquals('foo', $this->request->getContent());
    }}

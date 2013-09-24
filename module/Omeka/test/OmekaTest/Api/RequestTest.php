<?php
namespace OmekaTest\Api;

use Omeka\Api\Request;

class RequestTest extends \PHPUnit_Framework_TestCase
{
    protected $request;
    
    public function setUp()
    {
        $this->request = new Request;
    }
    
    protected $validOperations = array(
        'search','create','read','update','delete',
    );

    public function testUnsetPropertiesReturnNull()
    {
        $this->assertNull($this->request->getOperation());
        $this->assertNull($this->request->getResource());
    }

    public function testConstructorSetsProperties()
    {
        $request = new Request('search', 'foo');
        $this->assertEquals('search', $request->getOperation());
        $this->assertEquals('foo', $request->getResource());
    }

    public function testHasValidOperations()
    {
        $this->assertEquals($this->validOperations, Request::$validOperations);
    }

    public function testSetsAndGetsValidOperations()
    {
        foreach ($this->validOperations as $validOperation) {
            $this->request->setOperation($validOperation);
            $this->assertEquals($validOperation, $this->request->getOperation());
        }
    }

    /**
     * @expectedException Omeka\Api\Exception\InvalidArgumentException
     */
    public function testRejectsInvalidOperation()
    {
        $this->request->setOperation('foo');
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

    public function testSetsAndGetsData()
    {
        $this->request->setData('foo');
        $this->assertEquals('foo', $this->request->getData());
    }}

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

    public function testHasValidOperations()
    {
        $this->assertEquals($this->validOperations, Request::$validOperations);
    }

    public function testConstructorSetsProperties()
    {
        $request = new Request('search', 'foo', 1);
        $this->assertEquals('search', $request->getOperation());
        $this->assertEquals('foo', $request->getResource());
        $this->assertEquals(1, $request->getId());
    }

    public function testSetsAndGetsValidOperations()
    {
        foreach ($this->validOperations as $validOperation) {
            $this->request->setOperation($validOperation);
            $this->assertEquals($validOperation, $this->request->getOperation());
        }
    }

    public function testUnsetPropertiesReturnNull()
    {
        $this->assertNull($this->request->getOperation());
        $this->assertNull($this->request->getResource());
        $this->assertNull($this->request->getId());
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
        $this->request->setId(1);
        $this->assertEquals(1, $this->request->getId());
    }
}

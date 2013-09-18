<?php
namespace OmekaTest\Api;

use Omeka\Api\Request;

class RequestTest extends \PHPUnit_Framework_TestCase
{
    protected $validOperations = array(
        'search','create','read','update','delete',
    );

    public function testHasValidOperations()
    {
        $request = new Request;
        $this->assertEquals($this->validOperations, Request::$validOperations);
    }

    public function testConstructorSetsProperties()
    {
        $request = new Request('search', 'foo');
        $this->assertEquals('search', $request->getOperation());
        $this->assertEquals('foo', $request->getResource());
    }

    public function testSetsAndGetsValidOperations()
    {
        $request = new Request;
        foreach ($this->validOperations as $validOperation) {
            $request->setOperation($validOperation);
            $this->assertEquals($validOperation, $request->getOperation());
        }
    }

    public function testUnsetPropertiesReturnNull()
    {
        $request = new Request;
        $this->assertNull($request->getOperation());
        $this->assertNull($request->getResource());
    }

    /**
     * @expectedException Omeka\Api\Exception\InvalidArgumentException
     */
    public function testRejectsInvalidOperation()
    {
        $request = new Request;
        $request->setOperation('foo');
    }

    public function testSetsandGetsResource()
    {
        $request = new Request;
        $request->setResource('foo');
        $this->assertEquals('foo', $request->getResource());
    }
}

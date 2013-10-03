<?php
namespace OmekaTest\Api;

use Omeka\Api\Response;

class ResponseTest extends \PHPUnit_Framework_TestCase
{
    protected $response;

    public function setUp()
    {
        $this->response = new Response;
    }

    public function testUnsetPropertiesReturnNull()
    {
        $this->assertNull($this->response->getData());
        $this->assertNull($this->response->getRequest());
    }

    public function testConstructorSetsProperties()
    {
        $response = new Response('foo');
        $this->assertEquals('foo', $response->getData());
    }

    public function testSetsAndGetsData()
    {
        $this->response->setData('foo');
        $this->assertEquals('foo', $this->response->getData());
    }

    public function testSetsAndGetsRequest()
    {
        $request = $this->getMock('Omeka\Api\Request');
        $this->response->setRequest($request);
        $this->assertInstanceOf('Omeka\Api\Request', $this->response->getRequest());
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testRejectsInvalidRequest()
    {
        $this->response->setRequest(new \stdClass);
    }
}

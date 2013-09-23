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
        $this->assertNull($this->response->getResponse());
        $this->assertNull($this->response->getRequest());
    }

    public function testConstructorSetsProperties()
    {
        $request = $this->getMock('Omeka\Api\Request');
        $response = new Response('foo', $request);
        $this->assertEquals('foo', $response->getResponse());
        $this->assertSame($request, $response->getRequest());
    }

    public function testSetsAndGetsResponse()
    {
        $this->response->setResponse('foo');
        $this->assertEquals('foo', $this->response->getResponse());
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

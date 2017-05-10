<?php
namespace OmekaTest\Api;

use Omeka\Api\Response;
use Omeka\Test\TestCase;

class ResponseTest extends TestCase
{
    protected $response;

    protected $validStatuses = [
        'success', 'error_validation', 'error',
    ];

    public function setUp()
    {
        $this->response = new Response;
    }

    public function testInitialState()
    {
        $this->assertEquals('', $this->response->getContent());
        $this->assertNull($this->response->getRequest());
    }

    public function testConstructorSetsProperties()
    {
        $response = new Response('content');
        $this->assertEquals('content', $response->getContent());
    }

    public function testSetsAndGetsContent()
    {
        $this->response->setContent('content');
        $this->assertEquals('content', $this->response->getContent());
    }

    public function testSetsAndGetsRequest()
    {
        $request = $this->getMockBuilder('Omeka\Api\Request')
            ->disableOriginalConstructor()->getMock();
        $this->response->setRequest($request);
        $this->assertInstanceOf('Omeka\Api\Request', $this->response->getRequest());
    }
}

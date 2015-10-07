<?php
namespace OmekaTest\Api;

use Omeka\Api\Response;
use Omeka\Stdlib\ErrorStore;
use Omeka\Test\TestCase;

class ResponseTest extends TestCase
{
    protected $response;

    protected $validStatuses = [
        'success', 'error_internal', 'error_validation', 'error_not_found',
        'error_bad_request', 'error_bad_response', 'error_permission_denied',
    ];

    public function setUp()
    {
        $this->response = new Response;
    }

    public function testInitialState()
    {
        $this->assertEquals('', $this->response->getContent());
        $this->assertEquals('success', $this->response->getStatus());
        $this->assertInstanceOf('Omeka\Stdlib\ErrorStore', $this->response->getErrorStore());
        $this->assertEmpty($this->response->getErrors());
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

    public function testSetsAndGetsValidStatuses()
    {
        foreach ($this->validStatuses as $validStatus) {
            $this->response->setStatus($validStatus);
            $this->assertEquals($validStatus, $this->response->getStatus());
        }
    }

    public function testAddsAndGetsError()
    {
        $this->response->addError('foo', 'foo_message_one');
        $this->response->addError('foo', 'foo_message_two');
        $this->response->addError('bar', 'bar_message');
        $this->assertEquals($this->response->getErrors(), [
            'foo' => ['foo_message_one', 'foo_message_two'],
            'bar' => ['bar_message'],
        ]);
    }

    public function testMergesErrors()
    {
        $this->response->addError('foo', 'foo_message_one');
        $errorStore = new ErrorStore;
        $errorStore->addError('foo', 'foo_message_two');
        $errorStore->addError('bar', 'bar_message');
        $this->response->mergeErrors($errorStore);
        $this->assertEquals(
            [
                'foo' => [
                    'foo_message_one',
                    'foo_message_two',
                ],
                'bar' => ['bar_message'],
            ],
            $this->response->getErrors()
        );
    }

    public function testIsErrorWorks()
    {
        $this->response->setStatus('error');
        $this->assertTrue($this->response->isError());
        $this->response->setStatus('error_validation');
        $this->assertTrue($this->response->isError());
        $this->response->setStatus('success');
        $this->assertFalse($this->response->isError());
    }

    public function testSetsAndGetsRequest()
    {
        $request = $this->getMock('Omeka\Api\Request');
        $this->response->setRequest($request);
        $this->assertInstanceOf('Omeka\Api\Request', $this->response->getRequest());
    }
}

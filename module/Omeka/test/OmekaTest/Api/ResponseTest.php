<?php
namespace OmekaTest\Api;

use Omeka\Api\Response;

class ResponseTest extends \PHPUnit_Framework_TestCase
{
    protected $response;

    protected $validStatuses = array(
        'success', 'error_internal', 'error_validation', 'error_not_found',
    );

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

    public function testSetsAndGetsValidStatuses()
    {
        foreach ($this->validStatuses as $validStatus) {
            $this->response->setStatus($validStatus);
            $this->assertEquals($validStatus, $this->response->getStatus());
        }
    }

    /**
     * @expectedException Omeka\Api\Exception\InvalidArgumentException
     */
    public function testRejectsInvalidStatus()
    {
        $this->response->setStatus('foo');
    }

    public function testSetsAndGetsError()
    {
        $this->response->setError('foo', 'one');
        $this->response->setError('foo', 'two');
        $this->response->setError('bar', 'three');
        $this->assertEquals($this->response->getErrors(), array(
            'foo' => array('one', 'two'),
            'bar' => array('three'),
        ));
    }

    public function testSetErrorsWorks()
    {
        $this->response->setErrors(array(
            'foo' => array('one', 'two'),
            'bar' => array('three'),
        ));
        $this->assertEquals($this->response->getErrors(), array(
            'foo' => array('one', 'two'),
            'bar' => array('three'),
        ));
    }

    public function testIsErrorWorks()
    {
        $this->response->setStatus('error_internal');
        $this->assertTrue($this->response->isError());
        $this->response->setStatus('error_validation');
        $this->assertTrue($this->response->isError());
        $this->response->setStatus('error_not_found');
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

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testRejectsInvalidRequest()
    {
        $this->response->setRequest(new \stdClass);
    }
}

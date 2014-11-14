<?php
namespace OmekaTest\Api;

use Omeka\Api\ResponseFilter;
use Omeka\Test\TestCase;

class ResponseFilterTest extends TestCase
{
    public function testGet()
    {
        $responseContent = array(
            array('content_key' => 'content_value_one',
                  'key_one' => array('key_two' => array('key_three' => 'deep_content_one'))),
            array('content_key' => 'content_value_two',
                  'key_one' => array('key_two' => array('key_three' => 'deep_content_two'))),
        );

        $mockResponse = $this->getMock('Omeka\Api\Response', array(
            'isError', 'getContent', 'getRequest', 'getOperation'
        ));
        $mockResponse->expects($this->any())
            ->method('isError')
            ->will($this->returnValue(false));
        $mockResponse->expects($this->any())
            ->method('getContent')
            ->will($this->returnValue($responseContent));
        $mockResponse->expects($this->any())
            ->method('getRequest')
            ->will($this->returnSelf());
        // the search operation provides an adequate benchmark for other operations
        $mockResponse->expects($this->any())
            ->method('getOperation')
            ->will($this->returnValue('search'));

        $responseFilter = new ResponseFilter;

        $value = $responseFilter->get($mockResponse, 'content_key');
        $this->assertEquals(array('content_value_one', 'content_value_two'), $value);

        // one
        $value = $responseFilter->get($mockResponse, 'content_key', array('one' => true));
        $this->assertEquals('content_value_one', $value);

        // delimiter
        $value = $responseFilter->get($mockResponse, 'content_key', array('delimiter' => ', '));
        $this->assertEquals('content_value_one, content_value_two', $value);

        // default
        $value = $responseFilter->get($mockResponse, 'content_key', array(
            'default' => 'default_value',
            'default_if' => array('content_value_one'),
        ));
        $this->assertEquals(array('default_value', 'content_value_two'), $value);

        // one default
        $value = $responseFilter->get($mockResponse, 'content_key', array(
            'one' => true,
            'default' => 'default_value',
            'default_if' => array('content_value_one'),
        ));
        $this->assertEquals('default_value', $value);

        // delimiter default
        $value = $responseFilter->get($mockResponse, 'content_key', array(
            'delimiter' => ', ',
            'default' => 'default_value',
            'default_if' => array('content_value_one'),
        ));
        $this->assertEquals('default_value, content_value_two', $value);

        // deep key
        $value = $responseFilter->get($mockResponse, array('key_one', 'key_two', 'key_three'));
        $this->assertEquals(array('deep_content_one', 'deep_content_two'), $value);

        $value = $responseFilter->get($mockResponse, 'content_key', array(
            'callbacks' => array(function($value) {
                return 'callback_value';
            }),
        ));
        $this->assertEquals(array('callback_value', 'callback_value'), $value);
    }
}

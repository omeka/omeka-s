<?php
namespace OmekaTest\View\Helper;

use Omeka\View\Helper\Api;

class ApiTest extends \PHPUnit_Framework_TestCase
{
    public function testSearch()
    {
        $resource = 'resource';
        $data = array('data');
        $expectedReturnValue = 'search_return_value';

        $apiManager = $this->getMock('Omeka\Api\Manager');
        $apiManager->expects($this->once())
            ->method('search')
            ->with($this->equalTo($resource), $this->equalTo($data))
            ->will($this->returnValue($expectedReturnValue));

        $apiHelper = new Api($apiManager);
        $returnValue = $apiHelper->search($resource, $data);
        $this->assertEquals($expectedReturnValue, $returnValue);
    }

    public function testRead()
    {
        $resource = 'resource';
        $data = array('data');
        $expectedReturnValue = 'read_return_value';

        $apiManager = $this->getMock('Omeka\Api\Manager');
        $apiManager->expects($this->once())
            ->method('read')
            ->with($this->equalTo($resource), $this->equalTo($data))
            ->will($this->returnValue($expectedReturnValue));

        $apiHelper = new Api($apiManager);
        $returnValue = $apiHelper->read($resource, $data);
        $this->assertEquals($expectedReturnValue, $returnValue);
    }
}

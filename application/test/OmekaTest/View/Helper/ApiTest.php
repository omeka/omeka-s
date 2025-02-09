<?php
namespace OmekaTest\View\Helper;

use Omeka\View\Helper\Api;
use Omeka\Test\TestCase;

class ApiTest extends TestCase
{
    public function testSearch()
    {
        $resource = 'resource';
        $data = ['data'];
        $expectedReturnValue = 'search_return_value';

        $apiManager = $mockApiManager = $this->getMockBuilder('Omeka\Api\Manager')->disableOriginalConstructor()->getMock();
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
        $id = 'test-id';
        $data = ['data'];
        $expectedReturnValue = 'read_return_value';

        $apiManager = $mockApiManager = $this->getMockBuilder('Omeka\Api\Manager')->disableOriginalConstructor()->getMock();
        $apiManager->expects($this->once())
            ->method('read')
            ->with(
                $this->equalTo($resource),
                $this->equalTo($id),
                $this->equalTo($data)
            )
            ->will($this->returnValue($expectedReturnValue));

        $apiHelper = new Api($apiManager);
        $returnValue = $apiHelper->read($resource, $id, $data);
        $this->assertEquals($expectedReturnValue, $returnValue);
    }
}

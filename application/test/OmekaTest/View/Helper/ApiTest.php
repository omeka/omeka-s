<?php
namespace OmekaTest\View\Helper;

use Omeka\View\Helper\Api;
use Omeka\Test\TestCase;

class ApiTest extends TestCase
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
        $serviceManager = $this->getServiceManager(
            array('Omeka\ApiManager' => $apiManager)
        );

        $apiHelper = new Api($serviceManager);
        $returnValue = $apiHelper->search($resource, $data);
        $this->assertEquals($expectedReturnValue, $returnValue);
    }

    public function testRead()
    {
        $resource = 'resource';
        $id = 'test-id';
        $data = array('data');
        $expectedReturnValue = 'read_return_value';

        $apiManager = $this->getMock('Omeka\Api\Manager');
        $apiManager->expects($this->once())
            ->method('read')
            ->with(
                $this->equalTo($resource),
                $this->equalTo($id),
                $this->equalTo($data)
            )
            ->will($this->returnValue($expectedReturnValue));
        $serviceManager = $this->getServiceManager(
            array('Omeka\ApiManager' => $apiManager)
        );

        $apiHelper = new Api($serviceManager);
        $returnValue = $apiHelper->read($resource, $id, $data);
        $this->assertEquals($expectedReturnValue, $returnValue);
    }
}

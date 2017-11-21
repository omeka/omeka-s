<?php
namespace OmekaTest\Mvc\Controller\Plugin;

use Omeka\Mvc\Controller\Plugin\Api;
use Omeka\Test\TestCase;

class ApiTest extends TestCase
{
    public function testSearch()
    {
        $resource = 'test-resource';
        $data = ['foo' => 'bar'];

        $mockApiManager = $this->getMockBuilder('Omeka\Api\Manager')->disableOriginalConstructor()->getMock();
        $mockApiManager->expects($this->once())
            ->method('search')
            ->with($this->equalTo($resource), $this->equalTo($data))
            ->will($this->returnValue($this->createMock('Omeka\Api\Response')));

        $mockController = $this->getMockForAbstractClass('Zend\Mvc\Controller\AbstractController');

        $api = new Api($mockApiManager);
        $api->setController($mockController);
        $api->search($resource, $data);
    }

    public function testCreate()
    {
        $resource = 'test-resource';
        $data = ['foo' => 'bar'];

        $mockApiManager = $this->getMockBuilder('Omeka\Api\Manager')->disableOriginalConstructor()->getMock();
        $mockApiManager->expects($this->once())
            ->method('create')
            ->with($this->equalTo($resource), $this->equalTo($data))
            ->will($this->returnValue($this->createMock('Omeka\Api\Response')));

        $mockController = $this->getMockForAbstractClass('Zend\Mvc\Controller\AbstractController');

        $api = new Api($mockApiManager);
        $api->setController($mockController);
        $api->create($resource, $data);
    }

    public function testBatchCreate()
    {
        $resource = 'test-resource';
        $data = ['foo' => 'bar'];

        $mockApiManager = $this->getMockBuilder('Omeka\Api\Manager')->disableOriginalConstructor()->getMock();
        $mockApiManager->expects($this->once())
            ->method('batchCreate')
            ->with($this->equalTo($resource), $this->equalTo($data))
            ->will($this->returnValue($this->createMock('Omeka\Api\Response')));

        $mockController = $this->getMockForAbstractClass('Zend\Mvc\Controller\AbstractController');

        $api = new Api($mockApiManager);
        $api->setController($mockController);
        $api->batchCreate($resource, $data);
    }

    public function testRead()
    {
        $resource = 'test-resource';
        $id = 'test-id';
        $data = ['foo' => 'bar'];

        $mockApiManager = $this->getMockBuilder('Omeka\Api\Manager')->disableOriginalConstructor()->getMock();
        $mockApiManager->expects($this->once())
            ->method('read')
            ->with($this->equalTo($resource), $this->equalTo($id), $this->equalTo($data))
            ->will($this->returnValue($this->createMock('Omeka\Api\Response')));

        $mockController = $this->getMockForAbstractClass('Zend\Mvc\Controller\AbstractController');

        $api = new Api($mockApiManager);
        $api->setController($mockController);
        $api->read($resource, $id, $data);
    }

    public function testUpdate()
    {
        $resource = 'test-resource';
        $id = 'test-id';
        $data = ['foo' => 'bar'];

        $mockApiManager = $this->getMockBuilder('Omeka\Api\Manager')->disableOriginalConstructor()->getMock();
        $mockApiManager->expects($this->once())
            ->method('update')
            ->with($this->equalTo($resource), $this->equalTo($id), $this->equalTo($data))
            ->will($this->returnValue($this->createMock('Omeka\Api\Response')));

        $mockController = $this->getMockForAbstractClass('Zend\Mvc\Controller\AbstractController');

        $api = new Api($mockApiManager);
        $api->setController($mockController);
        $api->update($resource, $id, $data);
    }

    public function testDelete()
    {
        $resource = 'test-resource';
        $id = 'test-id';
        $data = ['foo' => 'bar'];

        $mockApiManager = $this->getMockBuilder('Omeka\Api\Manager')->disableOriginalConstructor()->getMock();
        $mockApiManager->expects($this->once())
            ->method('delete')
            ->with($this->equalTo($resource), $this->equalTo($id), $this->equalTo($data))
            ->will($this->returnValue($this->createMock('Omeka\Api\Response')));

        $mockController = $this->getMockForAbstractClass('Zend\Mvc\Controller\AbstractController');

        $api = new Api($mockApiManager);
        $api->setController($mockController);
        $api->delete($resource, $id, $data);
    }

    public function testSearchOneWithContent()
    {
        $resource = 'test-resource';
        $data = ['foo' => 'bar'];
        $content = ['foobar', 'bazbat'];
        $dataWithLimit = $data + ['limit' => 1];

        $response = new \Omeka\Api\Response;
        $response->setContent($content);

        $mockApiManager = $this->getMockBuilder('Omeka\Api\Manager')->disableOriginalConstructor()->getMock();
        $mockApiManager->expects($this->once())
            ->method('search')
            ->with($this->equalTo($resource), $this->equalTo($dataWithLimit))
            ->will($this->returnValue($response));

        $mockController = $this->getMockForAbstractClass('Zend\Mvc\Controller\AbstractController');

        $api = new Api($mockApiManager);
        $api->setController($mockController);
        $response = $api->searchOne($resource, $data);
        $this->assertEquals('foobar', $response->getContent());
    }

    public function testSearchOneWithoutContent()
    {
        $resource = 'test-resource';
        $data = ['foo' => 'bar'];
        $content = [];
        $dataWithLimit = $data + ['limit' => 1];

        $response = new \Omeka\Api\Response;
        $response->setContent($content);

        $mockApiManager = $this->getMockBuilder('Omeka\Api\Manager')->disableOriginalConstructor()->getMock();
        $mockApiManager->expects($this->once())
            ->method('search')
            ->with($this->equalTo($resource), $this->equalTo($dataWithLimit))
            ->will($this->returnValue($response));

        $mockController = $this->getMockForAbstractClass('Zend\Mvc\Controller\AbstractController');

        $api = new Api($mockApiManager);
        $api->setController($mockController);
        $response = $api->searchOne($resource, $data);
        $this->assertNull($response->getContent());
    }
}

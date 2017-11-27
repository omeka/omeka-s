<?php
namespace OmekaTest\Api;

use Omeka\Api\Request;
use Omeka\Api\Manager;
use Omeka\Test\TestCase;
use Zend\ServiceManager\Exception\ServiceNotFoundException;

class ManagerTest extends TestCase
{
    const TEST_RESOURCE = 'test_resource';

    protected $requestOperations = [
        Request::SEARCH, Request::CREATE, Request::READ,
        Request::UPDATE, Request::DELETE,
    ];

    public function testSearch()
    {
        $mockResponse = $this->getMockResponse(true);
        $manager = $this->getApiManager('search', $mockResponse);
        $response = $manager->search(self::TEST_RESOURCE, []);
        $this->assertInstanceOf('Omeka\Api\Response', $response);
    }

    public function testCreate()
    {
        $mockResponse = $this->getMockResponse(true);
        $manager = $this->getApiManager('create', $mockResponse);
        $response = $manager->create(self::TEST_RESOURCE, []);
        $this->assertInstanceOf('Omeka\Api\Response', $response);
    }

    public function testRead()
    {
        $mockResponse = $this->getMockResponse(true);
        $manager = $this->getApiManager('read', $mockResponse);
        $response = $manager->read(self::TEST_RESOURCE, 'test-id', []);
        $this->assertInstanceOf('Omeka\Api\Response', $response);
    }

    public function testUpdate()
    {
        $mockResponse = $this->getMockResponse(true);
        $manager = $this->getApiManager('update', $mockResponse);
        $response = $manager->update(self::TEST_RESOURCE, 'test-id', []);
        $this->assertInstanceOf('Omeka\Api\Response', $response);
    }

    public function testDelete()
    {
        $mockResponse = $this->getMockResponse(true);
        $manager = $this->getApiManager('delete', $mockResponse);
        $response = $manager->delete(self::TEST_RESOURCE, 'test-id', []);
        $this->assertInstanceOf('Omeka\Api\Response', $response);
    }

    public function testExecute()
    {
        // Test SCRUD request operations.
        foreach ($this->requestOperations as $operation) {
            $mockResponse = $this->getMockResponse(true);
            $manager = $this->getApiManager($operation, $mockResponse);

            $mockRequest = $this->getMockRequest($operation, 'foo');
            $response = $manager->execute($mockRequest);

            $this->assertInstanceOf('Omeka\Api\Response', $response);
        }
    }

    public function testExecuteRequiresValidResource()
    {
        $this->expectException(\Omeka\Api\Exception\BadRequestException::class);

        $mockResponse = $this->getMockResponse(true);
        $manager = $this->getApiManager(Request::SEARCH, $mockResponse, true, false);

        $mockRequest = $this->getMockRequest(Request::SEARCH, 'foo');
        $response = $manager->execute($mockRequest);
    }

    public function testExecuteRequiresAccess()
    {
        $this->expectException(\Omeka\Api\Exception\PermissionDeniedException::class);

        $mockResponse = $this->getMockResponse(true);
        $manager = $this->getApiManager(Request::SEARCH, $mockResponse, false);

        $mockRequest = $this->getMockRequest(Request::SEARCH, 'foo');
        $response = $manager->execute($mockRequest);
    }

    public function testExecuteRequiresValidResponse()
    {
        $this->expectException(\Omeka\Api\Exception\BadResponseException::class);

        $mockResponse = null;
        $manager = $this->getApiManager(Request::SEARCH, $mockResponse);

        $mockRequest = $this->getMockRequest(Request::SEARCH, 'foo');
        $response = $manager->execute($mockRequest);
    }

    protected function getApiManager($requestOperation, $mockResponse,
        $isAllowed = true, $validResource = true, $isBatchCreate = false
    ) {
        // Omeka\Logger
        $mockLogger = $this->createMock('Zend\Log\Logger');

        // MvcTranslator
        $mockTranslator = $this->createMock('Zend\I18n\Translator\Translator');
        $mockTranslator->expects($this->any())
            ->method('translate')
            ->will($this->returnArgument(0));

        // EventManager returned by adapter
        $mockEventManager = $this->createMock('Zend\EventManager\EventManager');
        $mockEventManager->expects($this->any())
            ->method('trigger')
            ->with($this->isInstanceOf('Zend\EventManager\Event'));

        // TestAdapter returned by the adapter manager
        $mockAdapter = $this->createMock('Omeka\Api\Adapter\AdapterInterface');
        $mockAdapter->expects($this->any())
            ->method('getResourceId')
            ->will($this->returnValue('Omeka\Api\Adapter\AdapterInterface'));
        $mockAdapter->expects($this->any())
            ->method('getRepresentation')
            ->will($this->returnValue(
                $this->createMock('Omeka\Api\Representation\RepresentationInterface')
            ));
        $mockAdapter->expects($this->any())
            ->method('getEventManager')
            ->will($this->returnValue($mockEventManager));
        if ($isBatchCreate) {
            $mockAdapter->expects($this->any())
                ->method('batchCreate')
                ->with($this->isInstanceOf('Omeka\Api\Request'))
                ->will($this->returnValue($mockResponse));
        } else {
            $mockAdapter->expects($this->any())
                ->method($requestOperation)
                ->with($this->isInstanceOf('Omeka\Api\Request'))
                ->will($this->returnValue($mockResponse));
        }

        // Omeka\ApiAdapterManager
        $mockAdapterManager = $this->getMockBuilder('Omeka\Api\Adapter\Manager')
            ->disableOriginalConstructor()
            ->getMock();
        if ($validResource) {
            $mockAdapterManager->expects($this->any())
                ->method('get')
                ->with($this->equalTo(self::TEST_RESOURCE))
                ->will($this->returnValue($mockAdapter));
        } else {
            $mockAdapterManager->expects($this->any())
                ->method('get')
                ->with($this->equalTo(self::TEST_RESOURCE))
                ->will($this->throwException(new ServiceNotFoundException));
        }

        // Omeka\Acl
        $mockAcl = $this->createMock('Omeka\Permissions\Acl');
        $mockAcl->expects($this->any())
            ->method('userIsAllowed')
            ->with(
                $this->equalTo($mockAdapter),
                $this->equalTo($requestOperation)
            )
            ->will($this->returnValue($isAllowed));

        return new Manager($mockAdapterManager, $mockAcl, $mockLogger, $mockTranslator);
    }

    protected function getMockResponse($isValidStatus)
    {
        $mockResource = $this->createMock(
            'Omeka\Api\ResourceInterface'
        );
        $mockResponse = $this->createMock('Omeka\Api\Response');
        $mockResponse->expects($this->any())
            ->method('getContent')
            ->will($this->returnValue($mockResource));
        $mockResponse->expects($this->any())
            ->method('setRequest')
            ->with($this->isInstanceOf('Omeka\Api\request'));
        return $mockResponse;
    }

    protected function getMockRequest($operation, $resource)
    {
        $request = $this->createMock('Omeka\Api\Request');
        $request->expects($this->any())
            ->method('getOperation')
            ->will($this->returnValue($operation));
        $request->expects($this->any())
            ->method('getResource')
            ->will($this->returnValue(self::TEST_RESOURCE));
        $request->expects($this->any())
            ->method('getContent')
            ->will($this->returnValue([]));
        return $request;
    }
}

<?php
namespace OmekaTest\Api;

use Omeka\Api\Request;
use Omeka\Api\Manager;
use Omeka\Test\TestCase;
use Zend\ServiceManager\Exception\ServiceNotFoundException;

class ManagerTest extends TestCase
{
    const TEST_RESOURCE = 'test_resource';

    protected $requestOperations = array(
        Request::SEARCH, Request::CREATE, Request::READ,
        Request::UPDATE, Request::DELETE
    );

    public function setUp()
    {
        $this->manager = new Manager;
    }

    public function testSearch()
    {
            $mockResponse = $this->getMockResponse(true);
            $this->setServiceManager('search', $mockResponse);
            $response = $this->manager->search(self::TEST_RESOURCE, array());
            $this->assertInstanceOf('Omeka\Api\Response', $response);
            $this->assertNull($response->getErrors());
    }

    public function testCreate()
    {
            $mockResponse = $this->getMockResponse(true);
            $this->setServiceManager('create', $mockResponse);
            $response = $this->manager->create(self::TEST_RESOURCE, array());
            $this->assertInstanceOf('Omeka\Api\Response', $response);
            $this->assertNull($response->getErrors());
    }

    public function testRead()
    {
            $mockResponse = $this->getMockResponse(true);
            $this->setServiceManager('read', $mockResponse);
            $response = $this->manager->read(self::TEST_RESOURCE, 'test-id', array());
            $this->assertInstanceOf('Omeka\Api\Response', $response);
            $this->assertNull($response->getErrors());
    }

    public function testUpdate()
    {
            $mockResponse = $this->getMockResponse(true);
            $this->setServiceManager('update', $mockResponse);
            $response = $this->manager->update(self::TEST_RESOURCE, 'test-id', array());
            $this->assertInstanceOf('Omeka\Api\Response', $response);
            $this->assertNull($response->getErrors());
    }

    public function testDelete()
    {
            $mockResponse = $this->getMockResponse(true);
            $this->setServiceManager('delete', $mockResponse);
            $response = $this->manager->delete(self::TEST_RESOURCE, 'test-id', array());
            $this->assertInstanceOf('Omeka\Api\Response', $response);
            $this->assertNull($response->getErrors());
    }

    public function testExecute()
    {
        // Test SCRUD request operations.
        foreach ($this->requestOperations as $operation) {
            $mockResponse = $this->getMockResponse(true);
            $this->setServiceManager($operation, $mockResponse);

            $mockRequest = $this->getMockRequest($operation, true);
            $response = $this->manager->execute($mockRequest);

            $this->assertInstanceOf('Omeka\Api\Response', $response);
            $this->assertNull($response->getErrors());
        }
    }

    public function testExecuteBatchCreate()
    {
        $mockResponse = $this->getMockResponse(true);
        $this->setServiceManager('batch_create', $mockResponse, true, true, true);

        $mockRequest = $this->getMockRequest('batch_create', true);
        $response = $this->manager->execute($mockRequest);

        $this->assertInstanceOf('Omeka\Api\Response', $response);
        $this->assertNull($response->getErrors());
    }

    public function testExecuteRequiresValidRequestOperation()
    {
        $mockResponse = $this->getMockResponse(true);
        $this->setServiceManager(Request::SEARCH, $mockResponse);

        $mockRequest = $this->getMockRequest(Request::SEARCH, false);
        $response = $this->manager->execute($mockRequest);

        $this->assertArrayHasKey('error_bad_request', $response->getErrors());
    }

    public function testExecuteRequiresValidResource()
    {
        $mockResponse = $this->getMockResponse(true);
        $this->setServiceManager(Request::SEARCH, $mockResponse, true, false);

        $mockRequest = $this->getMockRequest(Request::SEARCH, true);
        $response = $this->manager->execute($mockRequest);

        $this->assertArrayHasKey('error_bad_request', $response->getErrors());
    }

    public function testExecuteRequiresAccess()
    {
        $mockResponse = $this->getMockResponse(true);
        $this->setServiceManager(Request::SEARCH, $mockResponse, false);

        $mockRequest = $this->getMockRequest(Request::SEARCH, true);
        $response = $this->manager->execute($mockRequest);

        $this->assertArrayHasKey('error_permission_denied', $response->getErrors());
    }

    public function testExecuteRequiresValidResponse()
    {
        $mockResponse = null;
        $this->setServiceManager(Request::SEARCH, $mockResponse);

        $mockRequest = $this->getMockRequest(Request::SEARCH, true);
        $response = $this->manager->execute($mockRequest);

        $this->assertArrayHasKey('error_bad_response', $response->getErrors());
    }

    public function testExecuteRequiresValidResponseStatus()
    {
        $mockResponse = $this->getMockResponse(false);
        $this->setServiceManager(Request::SEARCH, $mockResponse);

        $mockRequest = $this->getMockRequest(Request::SEARCH, true);
        $response = $this->manager->execute($mockRequest);

        $this->assertArrayHasKey('error_bad_response', $response->getErrors());
    }

    protected function setServiceManager($requestOperation, $mockResponse,
        $isAllowed = true, $validResource = true, $isBatchCreate = false
    ) {
        // Omeka\Logger
        $mockLogger = $this->getMock('Zend\Log\Logger');

        // MvcTranslator
        $mockTranslator = $this->getMock('Zend\I18n\Translator\Translator');
        $mockTranslator->expects($this->any())
            ->method('translate')
            ->will($this->returnArgument(0));

        // EventManager returned by adapter
        $mockEventManager = $this->getMock('Zend\EventManager\EventManager');
        $mockEventManager->expects($this->any())
            ->method('trigger')
            ->with($this->isInstanceOf('Omeka\Event\Event'));

        // TestAdapter returned by the adapter manager
        $mockAdapter = $this->getMock('Omeka\Api\Adapter\AdapterInterface');
        $mockAdapter->expects($this->any())
            ->method('getResourceId')
            ->will($this->returnValue('Omeka\Api\Adapter\AdapterInterface'));
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
        $mockAdapterManager = $this->getMock('Omeka\Api\Adapter\Manager');
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
        $mockAcl = $this->getMock('Omeka\Permissions\Acl');
        $mockAcl->expects($this->any())
            ->method('isAllowed')
            ->with(
                $this->equalTo('current_user'),
                $this->equalTo($mockAdapter),
                $this->equalTo($requestOperation)
            )
            ->will($this->returnValue($isAllowed));

        $serviceManager = $this->getServiceManager(array(
            'Omeka\Logger' => $mockLogger,
            'MvcTranslator' => $mockTranslator,
            'Omeka\ApiAdapterManager' => $mockAdapterManager,
            'Omeka\Acl' => $mockAcl,
        ));
        $this->manager->setServiceLocator($serviceManager);
    }

    protected function getMockResponse($isValidStatus)
    {
        $mockRepresentation = $this->getMock(
            'Omeka\Api\Representation\RepresentationInterface'
        );
        $mockResponse = $this->getMock('Omeka\Api\Response');
        $mockResponse->expects($this->any())
            ->method('isValidStatus')
            ->with($this->equalTo('response_status'))
            ->will($this->returnValue($isValidStatus));
        $mockResponse->expects($this->any())
            ->method('getStatus')
            ->will($this->returnValue('response_status'));
        $mockResponse->expects($this->any())
            ->method('getContent')
            ->will($this->returnValue($mockRepresentation));
        $mockResponse->expects($this->any())
            ->method('setRequest')
            ->with($this->isInstanceOf('Omeka\Api\request'));
        return $mockResponse;

    }

    protected function getMockRequest($operation, $isValidOperation)
    {
        $request = $this->getMock('Omeka\Api\Request');
        $request->expects($this->any())
            ->method('isValidOperation')
            ->with($this->equalTo($operation))
            ->will($this->returnValue($isValidOperation));
        $request->expects($this->any())
            ->method('getOperation')
            ->will($this->returnValue($operation));
        $request->expects($this->any())
            ->method('getResource')
            ->will($this->returnValue(self::TEST_RESOURCE));
        $request->expects($this->any())
            ->method('getContent')
            ->will($this->returnValue(array()));
        return $request;
    }
}

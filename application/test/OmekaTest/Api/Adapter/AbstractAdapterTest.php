<?php
namespace OmekaTest\Api\Adapter;

use Omeka\Test\TestCase;

class AbstractAdapterTest extends TestCase
{
    public function setUp()
    {
        $this->adapter = $this->getMockForAbstractClass('Omeka\Api\Adapter\AbstractAdapter');
    }

    protected function getMockRequest()
    {
        return $this->getMockBuilder('Omeka\Api\Request')
            ->disableOriginalConstructor()->getMock();
    }

    public function testSearchRequiresImplementation()
    {
        $this->setServiceManager();
        $this->expectException('Omeka\Api\Exception\RuntimeException');
        $this->adapter->search($this->getMockRequest());
    }

    public function testCreateRequiresImplementation()
    {
        $this->setServiceManager();
        $this->expectException('Omeka\Api\Exception\RuntimeException');
        $this->adapter->create($this->getMockRequest());
    }

    public function testBatchCreateRequiresImplementation()
    {
        $this->setServiceManager();
        $this->expectException('Omeka\Api\Exception\RuntimeException');
        $this->adapter->batchCreate($this->getMockRequest());
    }

    public function testReadRequiresImplementation()
    {
        $this->setServiceManager();
        $this->expectException('Omeka\Api\Exception\RuntimeException');
        $this->adapter->read($this->getMockRequest());
    }

    public function testUpdateRequiresImplementation()
    {
        $this->setServiceManager();
        $this->expectException('Omeka\Api\Exception\RuntimeException');
        $this->adapter->update($this->getMockRequest());
    }

    public function testDeleteRequiresImplementation()
    {
        $this->setServiceManager();
        $this->expectException('Omeka\Api\Exception\RuntimeException');
        $this->adapter->delete($this->getMockRequest());
    }

    protected function setServiceManager()
    {
        // MvcTranslator
        $mockTranslator = $this->createMock('Zend\I18n\Translator\Translator');
        $mockTranslator->expects($this->any())
            ->method('translate')
            ->will($this->returnArgument(0));

        $mockAdapterManager = $this->getMockBuilder('Omeka\Api\Adapter\Manager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockAdapterManager->expects($this->any())
            ->method('get')
            ->with($this->equalTo('test_adapter'))
            ->will($this->returnValue('return_test_adapter'));

        $serviceManager = $this->getServiceManager([
            'MvcTranslator' => $mockTranslator,
            'Omeka\ApiAdapterManager' => $mockAdapterManager,
            'EventManager' => $this->createMock('Zend\EventManager\EventManager'),
        ]);
        $this->adapter->setServiceLocator($serviceManager);
    }
}

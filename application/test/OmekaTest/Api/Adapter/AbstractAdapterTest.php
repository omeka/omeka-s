<?php
namespace OmekaTest\Api\Adapter;

use Omeka\Test\TestCase;

class AbstractAdapterTest extends TestCase
{
    public function setUp()
    {
        $this->adapter = $this->getMockForAbstractClass('Omeka\Api\Adapter\AbstractAdapter');
    }

    public function testSearchRequiresImplementation()
    {
        $this->setServiceManager();
        $this->setExpectedException('Omeka\Api\Exception\RuntimeException');
        $this->adapter->search($this->getMock('Omeka\Api\Request'));
    }

    public function testCreateRequiresImplementation()
    {
        $this->setServiceManager();
        $this->setExpectedException('Omeka\Api\Exception\RuntimeException');
        $this->adapter->create($this->getMock('Omeka\Api\Request'));
    }

    public function testBatchCreateRequiresImplementation()
    {
        $this->setServiceManager();
        $this->setExpectedException('Omeka\Api\Exception\RuntimeException');
        $this->adapter->batchCreate($this->getMock('Omeka\Api\Request'));
    }

    public function testReadRequiresImplementation()
    {
        $this->setServiceManager();
        $this->setExpectedException('Omeka\Api\Exception\RuntimeException');
        $this->adapter->read($this->getMock('Omeka\Api\Request'));
    }

    public function testUpdateRequiresImplementation()
    {
        $this->setServiceManager();
        $this->setExpectedException('Omeka\Api\Exception\RuntimeException');
        $this->adapter->update($this->getMock('Omeka\Api\Request'));
    }

    public function testDeleteRequiresImplementation()
    {
        $this->setServiceManager();
        $this->setExpectedException('Omeka\Api\Exception\RuntimeException');
        $this->adapter->delete($this->getMock('Omeka\Api\Request'));
    }

    protected function setServiceManager()
    {
        // MvcTranslator
        $mockTranslator = $this->getMock('Zend\I18n\Translator\Translator');
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
            'EventManager' => $this->getMock('Zend\EventManager\EventManager'),
        ]);
        $this->adapter->setServiceLocator($serviceManager);
    }
}

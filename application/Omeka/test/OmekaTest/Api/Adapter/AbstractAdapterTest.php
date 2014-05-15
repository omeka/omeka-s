<?php
namespace OmekaTest\Api\Adapter;

use Omeka\Test\TestCase;

class AbstractAdapterTest extends TestCase
{
    public function setUp()
    {
        $this->adapter = $this->getMockForAbstractClass('Omeka\Api\Adapter\AbstractAdapter');
    }

    public function testSearchRequiresRequest()
    {
        $this->setExpectedException('PHPUnit_Framework_Error');
        $this->adapter->search(new \stdClass);
    }

    public function testSearchRequiresImplementation()
    {
        $this->setServiceManager();
        $this->setExpectedException('Omeka\Api\Exception\RuntimeException');
        $this->adapter->search($this->getMock('Omeka\Api\Request'));
    }

    public function testCreateRequiresRequest()
    {
        $this->setExpectedException('PHPUnit_Framework_Error');
        $this->adapter->create(new \stdClass);
    }

    public function testCreateRequiresImplementation()
    {
        $this->setServiceManager();
        $this->setExpectedException('Omeka\Api\Exception\RuntimeException');
        $this->adapter->create($this->getMock('Omeka\Api\Request'));
    }

    public function testBatchCreateRequiresRequest()
    {
        $this->setExpectedException('PHPUnit_Framework_Error');
        $this->adapter->batchCreate(new \stdClass);
    }

    public function testBatchCreateRequiresImplementation()
    {
        $this->setServiceManager();
        $this->setExpectedException('Omeka\Api\Exception\RuntimeException');
        $this->adapter->batchCreate($this->getMock('Omeka\Api\Request'));
    }

    public function testReadRequiresRequest()
    {
        $this->setExpectedException('PHPUnit_Framework_Error');
        $this->adapter->read(new \stdClass);
    }

    public function testReadRequiresImplementation()
    {
        $this->setServiceManager();
        $this->setExpectedException('Omeka\Api\Exception\RuntimeException');
        $this->adapter->read($this->getMock('Omeka\Api\Request'));
    }

    public function testUpdateRequiresRequest()
    {
        $this->setExpectedException('PHPUnit_Framework_Error');
        $this->adapter->update(new \stdClass);
    }

    public function testUpdateRequiresImplementation()
    {
        $this->setServiceManager();
        $this->setExpectedException('Omeka\Api\Exception\RuntimeException');
        $this->adapter->update($this->getMock('Omeka\Api\Request'));
    }

    public function testDeleteRequiresRequest()
    {
        $this->setExpectedException('PHPUnit_Framework_Error');
        $this->adapter->delete(new \stdClass);
    }

    public function testDeleteRequiresImplementation()
    {
        $this->setServiceManager();
        $this->setExpectedException('Omeka\Api\Exception\RuntimeException');
        $this->adapter->delete($this->getMock('Omeka\Api\Request'));
    }

    public function testGetApiUrlReturnsNull()
    {
        $this->assertNull($this->adapter->getApiUrl(array()));
    }

    public function testGetWebUrlReturnsNull()
    {
        $this->assertNull($this->adapter->getWebUrl(array()));
    }

    public function testGetAdapter()
    {
        $this->setServiceManager();
        $this->assertEquals(
            'return_test_adapter',
            $this->adapter->getAdapter('test_adapter')
        );
    }

    public function testGetDateTimeRequiresDateTime()
    {
        $this->setExpectedException('PHPUnit_Framework_Error');
        $this->adapter->getDateTime(new \stdClass);
    }

    public function testGetDateTimeReturnsStdlibDateTime()
    {
        $this->assertInstanceOf(
            'Omeka\Stdlib\DateTime',
            $this->adapter->getDateTime(new \DateTime)
        );
    }

    protected function setServiceManager()
    {
        // MvcTranslator
        $mockTranslator = $this->getMock('Zend\I18n\Translator\Translator');
        $mockTranslator->expects($this->any())
            ->method('translate')
            ->will($this->returnArgument(0));

        $mockAdapterManager = $this->getMock('Omeka\Api\Adapter\Manager');
        $mockAdapterManager->expects($this->any())
            ->method('get')
            ->with($this->equalTo('test_adapter'))
            ->will($this->returnValue('return_test_adapter'));

        $serviceManager = $this->getServiceManager(array(
            'MvcTranslator' => $mockTranslator,
            'Omeka\ApiAdapterManager' => $mockAdapterManager,
        ));
        $this->adapter->setServiceLocator($serviceManager);
    }
}

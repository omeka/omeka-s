<?php
namespace OmekaTest\Api;

use Omeka\Test\MockBuilder;

class AbstractAdapterTest extends \PHPUnit_Framework_TestCase
{
    protected $adapter;

    public function setUp()
    {
        $this->adapter = $this->getMockForAbstractClass('Omeka\Api\Adapter\AbstractAdapter');
    }

    public function testSetsAndGetsRequest()
    {
        $adapter = $this->getMockForAbstractClass('Omeka\Api\Adapter\AbstractAdapter');
        $request = $this->getMock('Omeka\Api\Request');
        $adapter->setRequest($request);
        $this->assertInstanceOf(
            'Omeka\Api\Request', 
            $adapter->getRequest()
        );
    }

    public function testSetsAndGetsServiceLocator()
    {
        $adapter = $this->getMockForAbstractClass('Omeka\Api\Adapter\AbstractAdapter');
        $serviceLocator = $this->getMock('Zend\ServiceManager\ServiceLocatorInterface');
        $adapter->setServiceLocator($serviceLocator);
        $this->assertInstanceOf(
            'Zend\ServiceManager\ServiceLocatorInterface', 
            $adapter->getServiceLocator()
        );
    }

    public function testGetsResourceId()
    {
        $adapter = $this->getMockForAbstractClass('Omeka\Api\Adapter\AbstractAdapter');
        $this->assertSame(
            strpos($adapter->getResourceId(), 'Mock_AbstractAdapter_'),
            0
        );
    }

    /**
     * @expectedException Omeka\Api\Exception\RuntimeException
     */
    public function testSearchStubThrowsError()
    {
        $this->setServiceLocator();
        $this->adapter->search();
    }

    /**
     * @expectedException Omeka\Api\Exception\RuntimeException
     */
    public function testCreateStubThrowsError()
    {
        $this->setServiceLocator();
        $this->adapter->create();
    }

    /**
     * @expectedException Omeka\Api\Exception\RuntimeException
     */
    public function testReadStubThrowsError()
    {
        $this->setServiceLocator();
        $this->adapter->read(1);
    }

    /**
     * @expectedException Omeka\Api\Exception\RuntimeException
     */
    public function testUpdateStubThrowsError()
    {
        $this->setServiceLocator();
        $this->adapter->update(1);
    }

    /**
     * @expectedException Omeka\Api\Exception\RuntimeException
     */
    public function testDeleteStubThrowsError()
    {
        $this->setServiceLocator();
        $this->adapter->delete(1);
    }

    protected function setServiceLocator()
    {
        $mockBuilder = new MockBuilder;
        $serviceLocator = $mockBuilder->getServiceManager(array(
            'MvcTranslator' => $this->getMock('Zend\I18n\Translator\Translator'),
        ));
        $this->adapter->setServiceLocator($serviceLocator);
    }
}

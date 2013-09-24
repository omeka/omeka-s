<?php
namespace OmekaTest\Api;

class AbstractAdapterTest extends \PHPUnit_Framework_TestCase
{
    public function testSetsAndGetsData()
    {
        $adapter = $this->getMockForAbstractClass('Omeka\Api\Adapter\AbstractAdapter');
        $data = array('foo' => 'bar');
        $adapter->setData($data);
        $this->assertEquals('bar', $adapter->getData('foo'));
        $this->assertEquals($data, $adapter->getData());
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

    /**
     * @expectedException Omeka\Api\Exception\RuntimeException
     */
    public function testSearchStubThrowsError()
    {
        $adapter = $this->getMockForAbstractClass('Omeka\Api\Adapter\AbstractAdapter');
        $adapter->search();
    }

    /**
     * @expectedException Omeka\Api\Exception\RuntimeException
     */
    public function testCreateStubThrowsError()
    {
        $adapter = $this->getMockForAbstractClass('Omeka\Api\Adapter\AbstractAdapter');
        $adapter->create();
    }

    /**
     * @expectedException Omeka\Api\Exception\RuntimeException
     */
    public function testReadStubThrowsError()
    {
        $adapter = $this->getMockForAbstractClass('Omeka\Api\Adapter\AbstractAdapter');
        $adapter->read(1);
    }

    /**
     * @expectedException Omeka\Api\Exception\RuntimeException
     */
    public function testUpdateStubThrowsError()
    {
        $adapter = $this->getMockForAbstractClass('Omeka\Api\Adapter\AbstractAdapter');
        $adapter->update(1);
    }

    /**
     * @expectedException Omeka\Api\Exception\RuntimeException
     */
    public function testDeleteStubThrowsError()
    {
        $adapter = $this->getMockForAbstractClass('Omeka\Api\Adapter\AbstractAdapter');
        $adapter->delete(1);
    }
}

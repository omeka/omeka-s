<?php
namespace OmekaTest\Api;

use Omeka\Api\Manager;

class ApiManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $config = array(
        'adapter_class' => 'Omeka\Api\Adapter\Db',
        'adapter_data' => array(
            'entity_class' => 'Omeka\Model\Entity\Site',
        ),
        'operations' => array(
            \Omeka\Api\Request::SEARCH,
        ),
    );
    
    /**
     * @expectedException Omeka\Api\Exception\ConfigException
     */
    public function testRegistrationRequiresAdapterClassKey()
    {
        $manager = new Manager;
        $config = $this->config;
        unset($config['adapter_class']);
        $manager->registerResource('foo', $config);
    }

    /**
     * @expectedException Omeka\Api\Exception\ConfigException
     */
    public function testRegistrationRequiresAdapterClass()
    {
        $manager = new Manager;
        $config = $this->config;
        $config['adapter_class'] = 'Foo';
        $manager->registerResource('foo', $config);
    }

    /**
     * @expectedException Omeka\Api\Exception\ConfigException
     */
    public function testAdapterClassImplementsAdapterInterface()
    {
        $manager = new Manager;
        $config = $this->config;
        $config['adapter_class'] = 'stdClass';
        $manager->registerResource('foo', $config);
    }

    /**
     * @expectedException Omeka\Api\Exception\ConfigException
     */
    public function testRegistrationRequiresOperationsKey()
    {
        $manager = new Manager;
        $config = $this->config;
        unset($config['operations']);
        $manager->registerResource('foo', $config);
    }

    /**
     * @expectedException Omeka\Api\Exception\ConfigException
     */
    public function testRegistrationRequiresOperationsArray()
    {
        $manager = new Manager;
        $config = $this->config;
        $config['operations'] = array();
        $manager->registerResource('foo', $config);
    }

    public function testSetsAndGetsServiceLocator()
    {
        $manager = new Manager;
        $serviceLocator = $this->getMock('Zend\ServiceManager\ServiceLocatorInterface');
        $manager->setServiceLocator($serviceLocator);
        $this->assertInstanceOf(
            'Zend\ServiceManager\ServiceLocatorInterface', 
            $manager->getServiceLocator()
        );
    }

    public function testExecutes()
    {
        //~ $manager = new Manager;
        //~ $manager->registerResource('foo', array(
            //~ 'adapter_class' => 'Omeka\Api\Adapter\Db',
            //~ 'adapter_data' => array(
                //~ 'entity_class' => 'Omeka\Model\Entity\Foo',
            //~ ),
            //~ 'functions' => array(
                //~ \Omeka\Api\Request::SEARCH,
            //~ ),
        //~ ));
        //~ $request = $this->getMock('Omeka\Api\Request');
    }

}

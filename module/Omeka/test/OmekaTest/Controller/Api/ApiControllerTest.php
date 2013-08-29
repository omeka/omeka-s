<?php
namespace OmekaTest\Controller;

use OmekaTest\Bootstrap;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class ApiControllerTest extends AbstractHttpControllerTestCase
{
    public function setUp()
    {
        $this->setApplicationConfig(Bootstrap::getApplicationConfig());
        parent::setUp();
    }
    
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/api/index');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('Omeka');
        $this->assertControllerName('Omeka\Controller\Api\Index');
        $this->assertControllerClass('IndexController');
        $this->assertMatchedRouteName('api');
    }    
   
    public function testResourceIdCanBeAccessed()
    {
        $this->dispatch('/api/res/12');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('Omeka');
        $this->assertControllerName('Omeka\Controller\Api\Index');
        $this->assertControllerClass('IndexController');
        $this->assertMatchedRouteName('api');
        $routeMatch = $this->getApplication()->getMvcEvent()->getRouteMatch();
        $this->assertEquals(12, $routeMatch->getParam('id'));
        $this->assertEquals('res', $routeMatch->getParam('resource'));
    }
    
    //@TODO: This isn't really relevant to the API, but is currently here for demo purposes only
    public function testIndexPageStructure()
    {
        $this->dispatch('/api/index');
        $this->assertQuery("p");
        $this->assertQueryContentContains("p", "index");
    }
    
    public function testResponseHeaders()
    {
        $this->dispatch('/api/index');
        $this->assertResponseHeaderContains('Content-Type', 'application/json');
    }
}
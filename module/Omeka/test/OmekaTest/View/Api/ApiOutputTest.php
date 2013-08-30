<?php
namespace OmekaTest\View;

use OmekaTest\Bootstrap;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class ViewApiOutputTest extends AbstractHttpControllerTestCase
{
    
    public function setUp()
    {
        $this->setApplicationConfig(Bootstrap::getApplicationConfig());
        parent::setUp();
    }    
    
    public function testIndexPageStructure()
    {
        $this->dispatch('/api/index');
        $response = $this->getApplication()->getMvcEvent()->getResponse();
        $responseArray = json_decode($response, true);
        $this->assertTrue(is_array($responseArray), "API response not valid JSON");
    }    
}
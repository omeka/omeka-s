<?php

namespace OmekaTest\Controller;

use Omeka\Test\AbstractHttpControllerTestCase;

class IndexControllerTest extends AbstractHttpControllerTestCase
{
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/');
        $this->assertResponseStatusCode(200);

        $this->assertModuleName('omeka');
        $this->assertControllerName('Omeka\Controller\Index');
        $this->assertControllerClass('IndexController');
        $this->assertMatchedRouteName('top');
    }
}

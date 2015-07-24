<?php
namespace OmekaTest\View\Helper;

use Omeka\Test\TestCase;
use Omeka\View\Helper\AssetUrl;

class AssetUrlTest extends TestCase
{
    protected $assetUrl;

    public function setUp()
    {
        $moduleManager = $this->getMock('Omeka\Module\Manager');
        $moduleManager->expects($this->once())
            ->method('getModulesByState')
            ->with($this->equalTo('active'))
            ->will($this->returnValue(array('MyModule' => array())));
        $themeManager = $this->getMock('Omeka\Theme\Manager');
        $serviceManager = $this->getServiceManager(
            array(
                'Omeka\ModuleManager' => $moduleManager,
                'Omeka\ThemeManager' => $themeManager,
            )
        );
        $view = $this->getMock('Zend\View\Renderer\PhpRenderer');
        $this->assetUrl = new AssetUrl($serviceManager);
        $this->assetUrl->setView($view);
    }

    public function testInvoke()
    {
        $assetUrl = $this->assetUrl;

        $url = $assetUrl('foo/bar', 'Omeka');
        $this->assertEquals('/application/asset/foo/bar', $url);
        
        $url = $assetUrl('baz/bat', 'MyModule');
        $this->assertEquals('/modules/MyModule/asset/baz/bat', $url);
    }
}

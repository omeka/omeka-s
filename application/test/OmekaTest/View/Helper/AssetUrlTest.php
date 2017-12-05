<?php
namespace OmekaTest\View\Helper;

use Omeka\Test\TestCase;
use Omeka\View\Helper\AssetUrl;

class AssetUrlTest extends TestCase
{
    protected $assetUrl;

    public function setUp()
    {
        $view = $this->createMock('Zend\View\Renderer\PhpRenderer');
        $this->assetUrl = new AssetUrl(
            'foo-theme',
            ['MyModule' => []],
            ['Omeka' => ['foo-internal' => 'foo-external']]
        );
        $this->assetUrl->setView($view);
    }

    public function testInvoke()
    {
        $assetUrl = $this->assetUrl;

        $url = $assetUrl('foo/bar', 'Omeka');
        $this->assertEquals('/application/asset/foo/bar', strtok($url, '?'));
    }

    public function testExternals()
    {
        $assetUrl = $this->assetUrl;

        $this->assertEquals('foo-external', $assetUrl('foo-internal', 'Omeka'));
    }
}

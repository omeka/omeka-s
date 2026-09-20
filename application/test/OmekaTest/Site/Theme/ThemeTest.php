<?php declare(strict_types=1);

namespace OmekaTest\Site\Theme;

use Omeka\Site\Theme\Theme;
use Omeka\Test\TestCase;

class ThemeTest extends TestCase
{
    public function testGetId()
    {
        $theme = new Theme('my-theme');
        $this->assertEquals('my-theme', $theme->getId());
    }

    public function testDefaultBasePath()
    {
        $theme = new Theme('my-theme');
        $this->assertEquals('themes', $theme->getBasePath());
    }

    public function testSetBasePath()
    {
        $theme = new Theme('my-theme');
        $theme->setBasePath('themes/custom');
        $this->assertEquals('themes/custom', $theme->getBasePath());
    }

    public function testGetPathWithDefaultBasePath()
    {
        $theme = new Theme('my-theme');
        $path = $theme->getPath();
        $this->assertStringEndsWith('/themes/my-theme', $path);
    }

    public function testGetPathWithCustomBasePath()
    {
        $theme = new Theme('my-theme');
        $theme->setBasePath('themes/custom');
        $path = $theme->getPath();
        $this->assertStringEndsWith('/themes/custom/my-theme', $path);
    }

    public function testGetPathWithSubsegments()
    {
        $theme = new Theme('my-theme');
        $theme->setBasePath('themes/custom');
        $path = $theme->getPath('view', 'layout');
        $this->assertStringEndsWith('/themes/custom/my-theme/view/layout', $path);
    }

    public function testGetThumbnailWithDefaultBasePath()
    {
        $theme = new Theme('my-theme');
        $thumbnail = $theme->getThumbnail();
        $this->assertEquals('/themes/my-theme/theme.jpg', $thumbnail);
    }

    public function testGetThumbnailWithCustomBasePath()
    {
        $theme = new Theme('my-theme');
        $theme->setBasePath('themes/custom');
        $thumbnail = $theme->getThumbnail();
        $this->assertEquals('/themes/custom/my-theme/theme.jpg', $thumbnail);
    }

    public function testGetThumbnailWithKeyUsesDefaultPath()
    {
        // When passing a key for another theme, the default path is used
        // because we don't know the basePath of the other theme.
        $theme = new Theme('my-theme');
        $theme->setBasePath('themes/custom');
        $thumbnail = $theme->getThumbnail('other-theme');
        $this->assertEquals('/themes/other-theme/theme.jpg', $thumbnail);
    }
}

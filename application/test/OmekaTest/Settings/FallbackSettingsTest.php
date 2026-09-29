<?php
namespace OmekaTest\Settings;

use Omeka\Service\Exception\RuntimeException;
use Omeka\Settings\FallbackSettings;
use Omeka\Settings\Settings;
use Omeka\Settings\SiteSettings;
use Omeka\Settings\UserSettings;
use Omeka\Test\TestCase;

class FallbackSettingsTest extends TestCase
{
    protected $settings;
    protected $siteSettings;
    protected $userSettings;
    protected $fallbackSettings;

    public function setUp(): void
    {
        $this->settings = $this->createMock(Settings::class);
        $this->siteSettings = $this->createMock(SiteSettings::class);
        $this->userSettings = $this->createMock(UserSettings::class);
        $this->fallbackSettings = new FallbackSettings(
            $this->settings,
            $this->siteSettings,
            $this->userSettings
        );
    }

    public function testReturnsFirstSourceWithAValue()
    {
        $this->userSettings->method('get')->willReturn(null);
        $this->siteSettings->method('get')->willReturn('site value');
        $this->settings->method('get')->willReturn('global value');

        $result = $this->fallbackSettings->getWithSource('foo', ['user', 'site', 'global']);
        $this->assertSame(['value' => 'site value', 'source' => 'site'], $result);
    }

    public function testRespectsSourceOrder()
    {
        $this->userSettings->method('get')->willReturn('user value');
        $this->settings->method('get')->willReturn('global value');

        $result = $this->fallbackSettings->getWithSource('foo', ['global', 'user']);
        $this->assertSame(['value' => 'global value', 'source' => 'global'], $result);
    }

    public function testNullAndEmptyStringFallThrough()
    {
        $this->userSettings->method('get')->willReturn('');
        $this->siteSettings->method('get')->willReturn(null);
        $this->settings->method('get')->willReturn('global value');

        $result = $this->fallbackSettings->getWithSource('foo', ['user', 'site', 'global']);
        $this->assertSame(['value' => 'global value', 'source' => 'global'], $result);
    }

    public function testEmptyArrayStopsTheFallback()
    {
        $this->userSettings->method('get')->willReturn([]);
        $this->siteSettings->expects($this->never())->method('get');

        $result = $this->fallbackSettings->getWithSource('foo', ['user', 'site']);
        $this->assertSame(['value' => [], 'source' => 'user'], $result);
    }

    public function testReturnsDefaultWithNullSourceWhenNoSourceHasAValue()
    {
        $this->userSettings->method('get')->willReturn(null);
        $this->siteSettings->method('get')->willReturn('');
        $this->settings->method('get')->willReturn(null);

        $result = $this->fallbackSettings->getWithSource('foo', ['user', 'site', 'global'], 'default');
        $this->assertSame(['value' => 'default', 'source' => null], $result);
    }

    public function testReadsEachSourceOnce()
    {
        $this->settings->expects($this->once())->method('get')->willReturn(null);

        $this->fallbackSettings->getWithSource('foo', ['global', 'global']);
    }

    public function testPassesTargetIdsThrough()
    {
        $this->userSettings->expects($this->once())
            ->method('get')
            ->with('foo', null, 7)
            ->willReturn(null);
        $this->siteSettings->expects($this->once())
            ->method('get')
            ->with('foo', null, 5)
            ->willReturn('site value');

        $result = $this->fallbackSettings->getWithSource('foo', ['user', 'site'], null, ['site' => 5, 'user' => 7]);
        $this->assertSame(['value' => 'site value', 'source' => 'site'], $result);
    }

    public function testUsesCurrentTargetsWithoutTargetIds()
    {
        $this->userSettings->expects($this->once())
            ->method('get')
            ->with('foo', null, null)
            ->willReturn(null);
        $this->siteSettings->expects($this->once())
            ->method('get')
            ->with('foo', null, null)
            ->willReturn(null);

        $this->fallbackSettings->getWithSource('foo', ['user', 'site']);
    }

    public function testSwallowsMissingTargetExceptions()
    {
        $this->userSettings->method('get')
            ->willThrowException(new RuntimeException('Cannot manage settings when no target ID is set.'));
        $this->siteSettings->method('get')
            ->willThrowException(new RuntimeException('Cannot manage settings when no target ID is set.'));
        $this->settings->method('get')->willReturn('global value');

        $result = $this->fallbackSettings->getWithSource('foo', ['user', 'site', 'global']);
        $this->assertSame(['value' => 'global value', 'source' => 'global'], $result);
    }

    public function testGetReturnsTheValueOnly()
    {
        $this->userSettings->method('get')->willReturn(null);
        $this->siteSettings->method('get')->willReturn('site value');

        $this->assertSame('site value', $this->fallbackSettings->get('foo', ['user', 'site']));
    }

    public function testGetReturnsTheDefaultWhenNoSourceHasAValue()
    {
        $this->settings->method('get')->willReturn(null);

        $this->assertSame('default', $this->fallbackSettings->get('foo', ['global'], 'default'));
    }
}

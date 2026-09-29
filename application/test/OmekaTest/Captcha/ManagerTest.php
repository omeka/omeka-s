<?php
namespace OmekaTest\Captcha;

use Omeka\Captcha\CaptchaInterface;
use Omeka\Captcha\Manager;
use Omeka\Test\TestCase;

class ManagerTest extends TestCase
{
    public function testGetActiveWithoutSelection()
    {
        $this->assertNull($this->getManager(null)->getActive());
        $this->assertNull($this->getManager('')->getActive());
    }

    public function testGetActiveWithUnregisteredProvider()
    {
        $this->assertNull($this->getManager('unregistered')->getActive());
    }

    public function testGetActiveWithUnconfiguredProvider()
    {
        $this->assertNull($this->getManager('unconfigured')->getActive());
    }

    public function testGetActiveWithConfiguredProvider()
    {
        $captcha = $this->getManager('configured')->getActive();
        $this->assertInstanceOf(CaptchaInterface::class, $captcha);
        $this->assertTrue($captcha->isConfigured());
    }

    protected function getManager($selected)
    {
        $settings = $this->createMock('Omeka\Settings\Settings');
        $settings->method('get')->with('captcha')->willReturn($selected);
        $services = $this->getServiceManager([
            'EventManager' => $this->getMockForAbstractClass('Laminas\EventManager\EventManagerInterface'),
            'MvcTranslator' => $this->getMockForAbstractClass('Laminas\I18n\Translator\TranslatorInterface'),
            'Omeka\Settings' => $settings,
        ]);
        return new Manager($services, [
            'factories' => [
                'configured' => fn () => $this->getCaptcha(true),
                'unconfigured' => fn () => $this->getCaptcha(false),
            ],
        ]);
    }

    protected function getCaptcha($isConfigured)
    {
        $captcha = $this->createMock(CaptchaInterface::class);
        $captcha->method('isConfigured')->willReturn($isConfigured);
        return $captcha;
    }
}

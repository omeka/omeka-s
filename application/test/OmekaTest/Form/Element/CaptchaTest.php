<?php
namespace OmekaTest\Form\Element;

use Omeka\Captcha\CaptchaInterface;
use Omeka\Form\Element\Captcha;
use Omeka\Test\TestCase;
use Laminas\Form\Form;

class CaptchaTest extends TestCase
{
    public function testWithoutProvider()
    {
        $element = new Captcha;
        $element->setCaptcha(null);
        $this->assertSame('captcha', $element->getName());

        $spec = $element->getInputSpecification();
        $this->assertSame('captcha', $spec['name']);
        $this->assertFalse($spec['required']);

        $element->setName('foo');
        $this->assertSame('foo', $element->getName());
    }

    public function testNameFollowsProvider()
    {
        $element = new Captcha;
        $element->setCaptcha($this->getCaptcha());
        $this->assertSame('test-captcha-response', $element->getName());

        $element->setName('foo');
        $this->assertSame('test-captcha-response', $element->getName());

        $spec = $element->getInputSpecification();
        $this->assertSame('test-captcha-response', $spec['name']);
        $this->assertTrue($spec['required']);
    }

    public function testIsValidVerifiesWithProvider()
    {
        $captcha = $this->getCaptcha();
        $captcha->expects($this->once())
            ->method('verify')
            ->with('token', '192.0.2.1')
            ->willReturn(true);

        $element = new Captcha;
        $element->setCaptcha($captcha);
        $element->setRemoteIp('192.0.2.1');
        $this->assertTrue($element->isValid('token'));
    }

    public function testEmptyResponseIsNotVerified()
    {
        $captcha = $this->getCaptcha();
        $captcha->expects($this->never())->method('verify');

        $element = new Captcha;
        $element->setCaptcha($captcha);
        $form = new Form;
        $form->add($element);
        $form->setData(['test-captcha-response' => '']);
        $this->assertFalse($form->isValid());
        $this->assertSame(['isEmpty'], array_keys($form->getMessages()['test-captcha-response']));
    }

    protected function getCaptcha()
    {
        $captcha = $this->createMock(CaptchaInterface::class);
        $captcha->method('getResponseName')->willReturn('test-captcha-response');
        return $captcha;
    }
}

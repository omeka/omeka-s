<?php declare(strict_types=1);

namespace OmekaTest\Form\View\Helper;

use Omeka\Form\Element\Secret;
use Omeka\Form\View\Helper\FormSecret;
use Omeka\Test\TestCase;
use Laminas\View\Renderer\PhpRenderer;

class FormSecretTest extends TestCase
{
    private function helper()
    {
        // Stub the renderer helpers used by FormSecret: formText/formPassword
        // echo the element name, type and value, the escapers and translator
        // are identity.
        $view = $this->getMockBuilder(PhpRenderer::class)
            ->onlyMethods(['__call'])
            ->getMock();
        $view->method('__call')->willReturnCallback(function ($name, $args) {
            if ($name === 'formText' || $name === 'formPassword') {
                $element = $args[0];
                $type = $name === 'formPassword' ? 'password' : 'text';
                return sprintf('<input type="%s" name="%s" value="%s">', $type, $element->getName(), $element->getValue());
            }
            return $args[0];
        });
        $helper = new FormSecret();
        $helper->setView($view);
        return $helper;
    }

    public function testNeverRendersTheStoredValue()
    {
        $element = new Secret('api_key');
        $element->setValue('super-secret');
        $output = $this->helper()->render($element);
        $this->assertStringNotContainsString('super-secret', $output);
    }

    public function testRendersATextInputByDefault()
    {
        $output = $this->helper()->render(new Secret('api_key'));
        $this->assertStringContainsString('type="text"', $output);
    }

    public function testRendersAPasswordInputWhenMasked()
    {
        $element = new Secret('api_key');
        $element->setOption('masked', true);
        $output = $this->helper()->render($element);
        $this->assertStringContainsString('type="password"', $output);
    }

    public function testNoRemoveControlWhenNoValueIsStored()
    {
        $output = $this->helper()->render(new Secret('api_key'));
        $this->assertStringNotContainsString('api_key_remove', $output);
    }

    public function testRemoveControlWhenAValueIsStored()
    {
        $element = new Secret('api_key');
        $element->setOption('has_value', true);
        $output = $this->helper()->render($element);
        $this->assertStringContainsString('name="api_key_remove"', $output);
        $this->assertStringContainsString('type="checkbox"', $output);
    }
}

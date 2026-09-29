<?php
namespace Omeka\Form\View\Helper;

use Laminas\Form\View\Helper\AbstractHelper;
use Laminas\Form\ElementInterface;

/**
 * Render a CAPTCHA element using its provider.
 *
 * Not named formCaptcha, which Laminas uses for Laminas\Form\Element\Captcha.
 */
class FormOmekaCaptcha extends AbstractHelper
{
    public function __invoke(ElementInterface $element)
    {
        return $this->render($element);
    }

    public function render(ElementInterface $element)
    {
        $captcha = $element->getCaptcha();
        return $captcha ? $captcha->render($this->getView(), $element) : '';
    }
}

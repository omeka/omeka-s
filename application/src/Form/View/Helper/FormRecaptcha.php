<?php
namespace Omeka\Form\View\Helper;

use Zend\Form\View\Helper\AbstractHelper;
use Zend\Form\ElementInterface;

class FormRecaptcha extends AbstractHelper
{
    public function __invoke(ElementInterface $element)
    {
        return $this->render($element);
    }

    public function render(ElementInterface $element)
    {
        $view = $this->getView();
        $view->headScript()->appendFile(
            $view->assetUrl('js/recaptcha.js', 'Omeka')
        );
        $view->headScript()->appendFile(
            'https://www.google.com/recaptcha/api.js?onload=recaptchaCallback&render=explicit',
            'text/javascript',
            ['async' => true, 'defer' => true]
        );
        return sprintf(
            '<div %s></div>',
            $this->createAttributesString($element->getAttributes())
        );
    }
}

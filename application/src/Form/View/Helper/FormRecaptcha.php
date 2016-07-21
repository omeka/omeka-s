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
        return sprintf(
            '<div %s></div>',
            $this->createAttributesString($element->getAttributes())
        );
    }
}

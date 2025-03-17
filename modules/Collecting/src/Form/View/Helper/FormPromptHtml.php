<?php
namespace Collecting\Form\View\Helper;

use Laminas\Form\View\Helper\AbstractHelper;
use Laminas\Form\ElementInterface;

class FormPromptHtml extends AbstractHelper
{
    public function __invoke(ElementInterface $element)
    {
        return $this->render($element);
    }

    public function render(ElementInterface $element)
    {
        return sprintf(
            '<div %s>%s</div>',
            $this->createAttributesString($element->getAttributes()),
            $element->getHtml()
        );
    }
}

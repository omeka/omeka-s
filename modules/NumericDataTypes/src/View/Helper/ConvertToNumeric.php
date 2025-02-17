<?php
namespace NumericDataTypes\View\Helper;

use Laminas\Form\View\Helper\AbstractHelper;
use Laminas\Form\ElementInterface;

class ConvertToNumeric extends AbstractHelper
{
    public function __invoke(ElementInterface $element)
    {
        return $this->render($element);
    }

    public function render(ElementInterface $element)
    {
        $view = $this->getView();
        return sprintf(
            '%s%s',
            $view->formSelect($element->getPropertyElement()),
            $view->formSelect($element->getTypeElement())
        );
    }
}

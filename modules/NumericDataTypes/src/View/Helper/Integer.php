<?php
namespace NumericDataTypes\View\Helper;

use Laminas\Form\View\Helper\AbstractHelper;
use Laminas\Form\ElementInterface;

class Integer extends AbstractHelper
{
    public function __invoke(ElementInterface $element)
    {
        return $this->render($element);
    }

    public function render(ElementInterface $element)
    {
        return $this->getView()->partial('common/numeric-integer', ['element' => $element]);
    }
}

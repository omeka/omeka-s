<?php
namespace Omeka\Form\View\Helper;

use Laminas\Form\View\Helper\AbstractHelper;
use Laminas\Form\ElementInterface;

class FormQuery extends AbstractHelper
{
    public function __invoke(ElementInterface $element)
    {
        return $this->render($element);
    }

    public function render(ElementInterface $element)
    {
        $view = $this->getView();
        parse_str($element->getValue(), $queryArray);
        return $view->partial('common/query-form', [
            'element' => $element,
            'queryArray' => $queryArray,
        ]);
    }
}

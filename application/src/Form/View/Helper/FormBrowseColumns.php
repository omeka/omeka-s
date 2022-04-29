<?php
namespace Omeka\Form\View\Helper;

use Laminas\Form\View\Helper\AbstractHelper;
use Laminas\Form\ElementInterface;

class FormBrowseColumns extends AbstractHelper
{
    public function __invoke(ElementInterface $element)
    {
        return $this->render($element);
    }

    public function render(ElementInterface $element)
    {
        $view = $this->getView();
        $columnsData = $view->browseColumns()->getColumnsData($element->getResourceType(), $element->getUserId());
        $defaultHeaders = [];
        foreach ($columnsData as $columnData) {
            $defaultHeaders[] = $view->browseColumns()->getHeader($columnData);
        }
        return $view->partial('common/browse-columns-form', [
            'element' => $element,
            'columnsData' => $columnsData,
            'defaultHeaders' => $defaultHeaders,
        ]);
    }
}

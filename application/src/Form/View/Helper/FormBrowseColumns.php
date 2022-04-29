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
        foreach ($columnsData as &$columnData) {
            // Add the default header to each column data.
            $columnData['header_default'] = $view->browseColumns()->getHeader($columnData);
        }
        return $view->partial('common/browse-columns-form', [
            'element' => $element,
            'columnsData' => $columnsData,
        ]);
    }
}

<?php
namespace Omeka\Form\View\Helper;

use Laminas\Form\ElementInterface;
use Laminas\Form\View\Helper\AbstractHelper;

class FormResourcePickerSelect extends AbstractHelper
{
    public function __invoke(ElementInterface $element)
    {
        return $this->render($element);
    }

    public function render(ElementInterface $element)
    {
        if (!$element->getOption('resource_partial')) {
            throw new \RuntimeException('ResourcePickerSelect requires a resource_partial option.');
        }
        if (!$element->getOption('api_resource')) {
            throw new \RuntimeException('ResourcePickerSelect requires an api_resource option.');
        }
        if (!$element->getOption('resources_endpoint_route')) {
            throw new \RuntimeException('ResourcePickerSelect requires a resources_endpoint_route option.');
        }
        $view = $this->getView();
        return $view->partial('common/resource-picker-select-form', [
            'element' => $element,
        ]);
    }
}

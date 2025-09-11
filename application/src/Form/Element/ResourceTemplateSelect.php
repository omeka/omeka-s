<?php
namespace Omeka\Form\Element;

class ResourceTemplateSelect extends AbstractGroupByOwnerSelect
{
    public function getResourceName()
    {
        return 'resource_templates';
    }

    public function getValueLabel($resource)
    {
        return $this->getTranslator()->translate($resource->label());
    }
}

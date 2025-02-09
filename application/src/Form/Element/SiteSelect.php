<?php
namespace Omeka\Form\Element;

class SiteSelect extends AbstractGroupByOwnerSelect
{
    public function getResourceName()
    {
        return 'sites';
    }

    public function getValueLabel($resource)
    {
        return $resource->title();
    }
}

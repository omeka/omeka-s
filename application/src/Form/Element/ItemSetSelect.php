<?php
namespace Omeka\Form\Element;

class ItemSetSelect extends AbstractGroupByOwnerSelect
{
    public function getResourceName()
    {
        return 'item_sets';
    }

    public function getValueLabel($resource)
    {
        $lang = ($this->options['lang'] ?? null);
        return $resource->displayTitle(null, $lang);
    }
}

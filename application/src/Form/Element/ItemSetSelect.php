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
        $lang = (isset($this->options['lang']) ? $this->options['lang'] : null);
        return $resource->displayTitle(null, $lang);
    }
}

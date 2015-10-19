<?php
namespace Omeka\View\Helper;

/**
 * A select menu containing all properties.
 */
class PropertySelect extends AbstractSelect
{
    protected $emptyOption = 'Select Property...';

    public function getValueOptions()
    {
        $vocabularies = $this->getView()->api()->search('vocabularies')->getContent();
        $valueOptions = [];
        foreach ($vocabularies as $vocabulary) {
            $options = [];
            foreach ($vocabulary->properties() as $property) {
                $options[$property->id()] = $property->label();
            }
            if (!$options) {
                continue;
            }
            $valueOptions[] = [
                'label' => $vocabulary->label(),
                'options' => $options,
            ];
        }
        return $valueOptions;
    }
}

<?php
namespace Omeka\View\Helper;

/**
 * A select menu containing all resource classes.
 */
class ResourceClassSelect extends AbstractSelect
{
    public function getValueOptions()
    {
        $vocabularies = $this->getView()->api()->search('vocabularies')->getContent();
        $valueOptions = [];
        foreach ($vocabularies as $vocabulary) {
            $options = [];
            foreach ($vocabulary->resourceClasses() as $resourceClass) {
                $options[$resourceClass->id()] = $resourceClass->label();
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

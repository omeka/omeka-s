<?php
namespace Omeka\View\Helper;

use Omeka\Api\Representation\UserRepresentation;

/**
 * A select menu containing all item sets.
 */
class ItemSetSelect extends AbstractSelect
{
    protected $emptyOption = 'Select Item Set...';

    public function getValueOptions()
    {
        $itemSets = $this->getView()->api()->search('item_sets')->getContent();

        // Group alphabetically by owner email.
        $itemSetOwners = [];
        foreach ($itemSets as $itemSet) {
            $owner = $itemSet->owner();
            $index = $owner ? $owner->email() : null;
            $itemSetOwners[$index]['owner'] = $owner;
            $itemSetOwners[$index]['item_sets'][] = $itemSet;
        }
        ksort($itemSetOwners);

        $valueOptions = [];
        foreach ($itemSetOwners as $itemSetOwner) {
            $options = [];
            foreach ($itemSetOwner['item_sets'] as $itemSet) {
                $options[$itemSet->id()] = $itemSet->displayTitle();
                if (!$options) {
                    continue;
                }
            }
            $owner = $itemSetOwner['owner'];
            if ($owner instanceof UserRepresentation) {
                $label = sprintf('%s (%s)', $owner->name(), $owner->email());
            } else {
                $label = '[No owner]';
            }
            $valueOptions[] = ['label' => $label, 'options' => $options];
        }
        return $valueOptions;
    }
}

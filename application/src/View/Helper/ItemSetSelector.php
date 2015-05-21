<?php
namespace Omeka\View\Helper;

use Zend\View\Helper\AbstractHelper;

class ItemSetSelector extends AbstractHelper
{
    /**
     * Return the item set selector form control.
     *
     * @return string
     */
    public function __invoke()
    {
        $query = array('is_open' => true);
        $response = $this->getView()->api()->search('item_sets', $query);
        if ($response->isError()) {
            return;
        }

        // Organize items sets by owner.
        $itemSetOwners = array();
        foreach ($response->getContent() as $itemSet) {
            $owner = $itemSet->owner();
            $ownerId = $owner ? $owner->id() : null;
            $itemSetOwners[$ownerId]['owner'] = $owner;
            $itemSetOwners[$ownerId]['item_sets'][] = $itemSet;
        }

        return $this->getView()->partial(
            'common/item-set-selector',
            array('itemSetOwners' => $itemSetOwners)
        );
    }
}

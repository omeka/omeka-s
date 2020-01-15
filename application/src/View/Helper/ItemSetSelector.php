<?php
namespace Omeka\View\Helper;

use Zend\View\Helper\AbstractHelper;

/**
 * View helper for rendering the item set selector form control.
 */
class ItemSetSelector extends AbstractHelper
{
    /**
     * Return the item set selector form control.
     *
     * @param bool $includeClosedSets Whether to include closed
     *  sets in the options available from the selector.
     * @return string
     */
    public function __invoke($includeClosedSets = false)
    {
        $query = ['sort_by' => 'owner_name'];
        if (!$includeClosedSets) {
            $query['is_open'] = true;
        }
        $response = $this->getView()->api()->search('item_sets', $query);

        // Organize items sets by owner.
        $itemSetOwners = [];
        foreach ($response->getContent() as $itemSet) {
            $owner = $itemSet->owner();
            $email = $owner ? $owner->email() : null;
            $itemSetOwners[$email]['owner'] = $owner;
            $itemSetOwners[$email]['item_sets'][] = $itemSet;
        }

        return $this->getView()->partial(
            'common/item-set-selector',
            [
                'itemSetOwners' => $itemSetOwners,
                'totalItemSetCount' => $response->getTotalResults(),
            ]
        );
    }
}

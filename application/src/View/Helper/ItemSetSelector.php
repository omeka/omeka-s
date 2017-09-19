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
     * @return string
     */
    public function __invoke()
    {
        $query = ['is_open' => true, 'sort_by' => 'owner_name'];
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

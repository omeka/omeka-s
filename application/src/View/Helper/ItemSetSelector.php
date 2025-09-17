<?php
namespace Omeka\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Omeka\Form\Element\SelectSortTrait;

/**
 * View helper for rendering the item set selector form control.
 */
class ItemSetSelector extends AbstractHelper
{
    use SelectSortTrait;

    /**
     * Return the item set selector form control.
     *
     * @param bool $includeClosedSets Whether to include closed
     *  sets in the options available from the selector.
     * @return string
     */
    public function __invoke($includeClosedSets = false)
    {
        $view = $this->getView();

        $query = ['sort_by' => 'owner_name'];
        if (!$includeClosedSets) {
            $query['is_open'] = true;
        }
        $response = $this->getView()->api()->search('item_sets', $query);

        // Organize items sets by owner.
        $options = [];
        foreach ($response->getContent() as $itemSet) {
            $owner = $itemSet->owner();
            $email = $owner ? $owner->email() : null;
            $options[$email]['owner'] = $owner;
            $options[$email]['label'] = $owner ? $owner->name() : $view->translate('[No owner]');
            $options[$email]['options'][] = [
                'label' => $itemSet->displayTitle(),
                'item_set' => $itemSet,
            ];
        }
        $options = $this->sortSelectOptions($options);
        return $view->partial(
            'common/item-set-selector',
            [
                'options' => $options,
                'totalCount' => $response->getTotalResults(),
            ]
        );
    }

}

<?php
namespace Omeka\Form\Element;

use Omeka\Api\Manager as ApiManager;
use Omeka\Api\Representation\UserRepresentation;
use Zend\Form\Element\Select;

class ItemSetSelect extends Select
{
    /**
     * @var ApiManager
     */
    protected $apiManager;

    public function getValueOptions()
    {
        $valueOptions = [];
        $response = $this->getApiManager()->search('item_sets');
        if (!$response->isError()) {
            // Group alphabetically by owner email.
            $itemSetOwners = [];
            foreach ($response->getContent() as $itemSet) {
                $owner = $itemSet->owner();
                $index = $owner ? $owner->email() : null;
                $itemSetOwners[$index]['owner'] = $owner;
                $itemSetOwners[$index]['item_sets'][] = $itemSet;
            }
            ksort($itemSetOwners);
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
        }
        return $valueOptions;
    }

    /**
     * @param ApiManager $apiManager
     */
    public function setApiManager(ApiManager $apiManager)
    {
        $this->apiManager = $apiManager;
    }

    /**
     * @return ApiManager
     */
    public function getApiManager()
    {
        return $this->apiManager;
    }
}

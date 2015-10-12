<?php
namespace Omeka\View\Helper;

use Zend\Form\Element\Select;
use Zend\View\Helper\AbstractHelper;

class ItemSetSelect extends AbstractHelper 
{
    /**
     * @var string Select element markup cache
     */
    protected $selectMarkup;

    /**
     * Return the property select element markup.
     *
     * @param string $name
     * @param array $attributes
     * @param string $emptyOption
     * @return string
     */
    public function __invoke($name, array $attributes = [],
        $emptyOption = 'Select Item Set'
    ) {
        if ($this->selectMarkup) {
            // Build the select markup only once.
            return $this->selectMarkup;
        }

        $response = $this->getView()->api()->search('item_sets');
        if ($response->isError()) {
            return;
        }

        foreach ($response->getContent() as $itemSet) {
            $owner = $itemSet->owner();
            $email = $owner ? $owner->email() : null;
            $options = [];
            $itemSetOwners[$email]['owner'] = $owner;
            $itemSetOwners[$email]['item_sets'][] = $itemSet;
        }
        ksort($itemSetOwners);

        $valueOptions = [];
        foreach ($itemSetOwners as $itemSetOwner) {
            foreach ($itemSetOwner['item_sets'] as $itemSet) {
                $options[$itemSet->id()] = $itemSet->displayTitle();
                if (!$options) {
                    continue;
                }
            }
            $valueOptions[] = [
                'label' => $itemSetOwner['owner']->name() . ' (' . $itemSetOwner['owner']->email() . ')',
                'options' => $options,
            ];
            $options = [];
        }

        $select = new Select;
        $select->setValueOptions($valueOptions)
            ->setName($name)
            ->setAttributes($attributes)
            ->setEmptyOption($emptyOption);

        // Cache the select markup.
        $this->selectMarkup = $this->getView()->formSelect($select);
        return $this->selectMarkup;
    }
}
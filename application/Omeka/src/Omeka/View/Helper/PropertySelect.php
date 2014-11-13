<?php
namespace Omeka\View\Helper;

use Zend\Form\Element\Select;
use Zend\View\Helper\AbstractHelper;

class PropertySelect extends AbstractHelper
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
    public function __invoke($name, array $attributes = array(),
        $emptyOption = 'Select Property'
    ) {
        if ($this->selectMarkup) {
            // Build the select markup only once.
            return $this->selectMarkup;
        }

        $response = $this->getView()->api()->search('vocabularies');
        if ($response->isError()) {
            return;
        }

        $valueOptions = array();
        foreach ($response->getContent() as $vocabulary) {
            $options = array();
            foreach ($vocabulary->properties() as $property) {
                $options[$property->id()] = $property->label();
            }
            if (!$options) {
                continue;
            }
            $valueOptions[] = array(
                'label' => $vocabulary->label(),
                'options' => $options,
            );
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

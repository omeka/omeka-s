<?php
namespace Omeka\View\Helper;

use Zend\Form\Element\Select;
use Zend\View\Helper\AbstractHelper;

class ResourceClassSelect extends AbstractHelper
{
    /**
     * @var string Select element markup cache
     */
    protected $selectMarkup;

    /**
     * Return the resource class select element markup.
     *
     * @param string $name
     * @param array $attributes
     * @param string $emptyOption
     * @return string
     */
    public function __invoke($name, array $attributes = array(),
        $emptyOption = 'Select Class'
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
            foreach ($vocabulary->resourceClasses() as $resourceClass) {
                $options[$resourceClass->id()] = $resourceClass->label();
            }
            if (!$options) {
                continue;
            }
            $valueOptions[] = array(
                'label' => $vocabulary->label(),
                'options' => $options,
            );
        }

        if (! isset($attributes['id'])) {
            $attributes['id'] = 'resource-class-select';
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

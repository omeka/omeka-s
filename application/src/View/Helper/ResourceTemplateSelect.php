<?php
namespace Omeka\View\Helper;

use Zend\Form\Element\Select;
use Zend\View\Helper\AbstractHelper;

class ResourceTemplateSelect extends AbstractHelper
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
        $emptyOption = 'Select Template'
    ) {
        if ($this->selectMarkup) {
            // Build the select markup only once.
            return $this->selectMarkup;
        }

        $response = $this->getView()->api()->search('resource_templates', array());
        if ($response->isError()) {
            return;
        }

        $options = array();
        $content = $response->getContent();
        if (! $content) {
            return;
        }
        
        foreach ($response->getContent() as $resourceTemplate) {
           $options[$resourceTemplate->id()] = $resourceTemplate->label();
        }

        if (! isset($attributes['id'])) {
            $attributes['id'] = 'resource-template-select';
        }

        $attributes['data-api-base-url'] = $this->getView()->url('api') .  '/resource_templates/';
        $select = new Select;
        $select->setValueOptions($options)
            ->setName($name)
            ->setAttributes($attributes)
            ->setEmptyOption($emptyOption);

        // Cache the select markup.
        $this->selectMarkup = $this->getView()->formSelect($select);
        return $this->selectMarkup;
    }
}

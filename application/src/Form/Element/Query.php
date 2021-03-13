<?php
namespace Omeka\Form\Element;

use Laminas\Form\Element;
use Laminas\InputFilter\InputProviderInterface;

class Query extends Element implements InputProviderInterface
{
    protected $attributes = [
        'class' => 'query-form-query',
    ];

    protected $resourceType = 'items';

    protected $partialExcludelist = [];

    public function setOptions($options)
    {
        parent::setOptions($options);
        if (in_array($this->getOption('query_resource_type'), ['items', 'item_sets', 'media'])) {
            $this->resourceType = $this->getOption('query_resource_type');
        }
        if (is_array($this->getOption('query_partial_excludelist'))) {
            $this->partialExcludelist = $this->getOption('query_partial_excludelist');
        }
    }

    public function getInputSpecification()
    {
        return [];
    }

    public function getResourceType()
    {
        return $this->resourceType;
    }

    public function getPartialExcludelist()
    {
        return $this->partialExcludelist;
    }
}

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
        $this->setResourceType($this->getOption('query_resource_type'));
        $this->setPartialExcludelist($this->getOption('query_partial_excludelist'));
        return $this;
    }

    public function getInputSpecification()
    {
        return [];
    }

    public function setResourceType($resourceType)
    {
        if (in_array($resourceType, ['items', 'item_sets', 'media'])) {
            $this->resourceType = $resourceType;
        }
        return $this;
    }

    public function getResourceType()
    {
        return $this->resourceType;
    }

    public function setPartialExcludelist($partialExcludelist)
    {
        if (is_array($partialExcludelist)) {
            $this->partialExcludelist = $partialExcludelist;
        }
        return $this;
    }

    public function getPartialExcludelist()
    {
        return $this->partialExcludelist;
    }
}

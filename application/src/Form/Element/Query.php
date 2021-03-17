<?php
namespace Omeka\Form\Element;

use Laminas\Form\Element;
use Laminas\InputFilter\InputProviderInterface;

class Query extends Element implements InputProviderInterface
{
    protected $attributes = [
        'class' => 'query-form-query',
    ];

    public function getInputSpecification()
    {
        return [];
    }

    public function getResourceType()
    {
        $resourceType = 'items';
        if (in_array($this->getOption('query_resource_type'), ['items', 'item_sets', 'media'])) {
            $resourceType = $this->getOption('query_resource_type');
        }
        return $resourceType;
    }

    public function getPartialExcludelist()
    {
        $partialExcludelist = [];
        if (is_array($this->getOption('query_partial_excludelist'))) {
            $partialExcludelist = $this->getOption('query_partial_excludelist');
        }
        return $partialExcludelist;
    }
}

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
        return [
            // Not required by default because an empty string is meaningful (all resources).
            'required' => false,
        ];
    }

    /**
     * Get the resource type of this query.
     *
     * @return string
     */
    public function getResourceType()
    {
        $resourceType = 'items';
        if (in_array($this->getOption('query_resource_type'), ['items', 'item_sets', 'media'])) {
            $resourceType = $this->getOption('query_resource_type');
        }
        return $resourceType;
    }

    /**
     * Get partials to exclude from the advanced search form.
     *
     * @return array
     */
    public function getPartialExcludelist()
    {
        $partialExcludelist = [];
        if (is_array($this->getOption('query_partial_excludelist'))) {
            $partialExcludelist = $this->getOption('query_partial_excludelist');
        }
        return $partialExcludelist;
    }

    /**
     * Get additional parameters to append to the preview query.
     *
     * This element may be used in a context that needs additional query
     * parameters for an accurate preview, such as ['site_id' => 1].
     *
     * @return array
     */
    public function getPreviewAppendQuery()
    {
        $appendQuery = [];
        if (is_array($this->getOption('query_preview_append_query'))) {
            $appendQuery = $this->getOption('query_preview_append_query');
        }
        return $appendQuery;
    }
}

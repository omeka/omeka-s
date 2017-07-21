<?php
namespace Omeka\Form\Element;

use Omeka\Api\Manager as ApiManager;
use Zend\Form\Element\Select;

abstract class AbstractVocabularyMemberSelect extends Select
{
    /**
     * @var ApiManager
     */
    protected $apiManager;

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

    /**
     * Get the resource name.
     *
     * @return string
     */
    abstract public function getResourceName();

    public function getValueOptions()
    {
        $resourceName = $this->getResourceName();

        $termAsValue = $this->getOption('term_as_value');
        $query = $this->getOption('query');
        if (!is_array($query)) {
            $query = [];
        }
        if (!isset($query['sort_by'])) {
            $query['sort_by'] = 'label';
        }

        $valueOptions = [];
        $response = $this->getApiManager()->search($resourceName, $query);
        foreach ($response->getContent() as $member) {
            $attributes = ['data-term' => $member->term()];
            if ('properties' === $resourceName) {
                $attributes['data-property-id'] = $member->id();
            } elseif ('resource_classes' === $resourceName) {
                $attributes['data-resource-class-id'] = $member->id();
            }
            $option = [
                'label' => $member->label(),
                'value' => $termAsValue ? $member->term() : $member->id(),
                'attributes' => $attributes,
            ];
            $vocabulary = $member->vocabulary();
            if (!isset($valueOptions[$vocabulary->prefix()])) {
                $valueOptions[$vocabulary->prefix()] = [
                    'label' => $vocabulary->label(),
                    'options' => [],
                ];
            }
            $valueOptions[$vocabulary->prefix()]['options'][] = $option;
        }

        // Move Dublin Core vocabularies (dcterms & dctype) to the beginning.
        if (isset($valueOptions['dcterms'])) {
            $valueOptions = ['dcterms' => $valueOptions['dcterms']] + $valueOptions;
        }
        if (isset($valueOptions['dctype'])) {
            $valueOptions = ['dctype' => $valueOptions['dctype']] + $valueOptions;
        }

        $prependValueOptions = $this->getOption('prepend_value_options');
        if (is_array($prependValueOptions)) {
            $valueOptions = $prependValueOptions + $valueOptions;
        }
        return $valueOptions;
    }
}

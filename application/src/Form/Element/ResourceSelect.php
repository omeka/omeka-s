<?php
namespace Omeka\Form\Element;

use Omeka\Api\Manager as ApiManager;
use Zend\Form\Element\Select;

class ResourceSelect extends Select
{
    /**
     * @var ApiManager
     */
    protected $apiManager;

    public function setOptions($options)
    {
        parent::setOptions($options);

        $resource = null;
        if (isset($options['resource_value_options']['resource'])
            && is_string($options['resource_value_options']['resource'])
        ) {
            $resource = $options['resource_value_options']['resource'];
        }

        $callback = null;
        if (isset($options['resource_value_options']['option_text_callback'])
            && is_callable($options['resource_value_options']['option_text_callback'])
        ) {
            $callback = $options['resource_value_options']['option_text_callback'];
        }

        $query = [];
        if (isset($options['resource_value_options']['query'])
            && is_array($options['resource_value_options']['query'])
        ) {
            $query = $options['resource_value_options']['query'];
        }

        $this->setResourceValueOptions($resource, $callback, $query);
        return $this;
    }

    /**
     * Set API resources as value options.
     *
     * Sets the resource ID as the option value and the return value of
     * $callback as the option text. The callback receives the resource
     * representation.
     */
    public function setResourceValueOptions($resource, callable $callback, array $query = [])
    {
        $valueOptions = [];
        $response = $this->getApiManager()->search($resource, $query);
        foreach ($response->getContent() as $representation) {
            $value = $callback($representation);
            if (is_array($value)) {
                if (!isset($valueOptions[$value[0]])) {
                    $valueOptions[$value[0]]['label'] = $value[0];
                }
                $valueOptions[$value[0]]['options'][$representation->id()] = $value[1];
            } else {
                $valueOptions[$representation->id()] = $value;
            }
        }
        $prependValueOptions = $this->getOption('prepend_value_options');
        if (is_array($prependValueOptions)) {
            $valueOptions = $prependValueOptions + $valueOptions;
        }
        $this->setValueOptions($valueOptions);
        return $this;
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

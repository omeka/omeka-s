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

    /**
     * Set API resources as value options.
     *
     * Sets the resource ID as the option value and the return value of
     * $callback as the option text. The callback receives the resource
     * representation.
     *
     * @param string $resource The API resource name
     * @param array $query The API request query
     * @param callable $callback A callback that returns option text
     */
    public function setResourceValueOptions($resource, array $query, callable $callback) {

        $response = $this->getApiManager()->search($resource, $query);
        if ($response->isError()) {
            return;
        }

        $valueOptions = [];
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
        return $this->setValueOptions($valueOptions);
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

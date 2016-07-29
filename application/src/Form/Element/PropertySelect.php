<?php
namespace Omeka\Form\Element;

use Omeka\Api\Manager as ApiManager;
use Zend\Form\Element\Select;

class PropertySelect extends Select
{
    /**
     * @var ApiManager
     */
    protected $apiManager;

    public function getValueOptions()
    {
        $valueOptions = [];
        $response = $this->getApiManager()->search('vocabularies');
        if (!$response->isError()) {
            foreach ($response->getContent() as $vocabulary) {
                $options = [];
                foreach ($vocabulary->properties() as $property) {
                    $options[] = [
                        'label' => $property->label(),
                        'value' => $property->id(),
                        'attributes' => ['data-term' => $property->term()],
                    ];
                }
                if (!$options) {
                    continue;
                }
                $valueOptions[] = [
                    'label' => $vocabulary->label(),
                    'options' => $options,
                ];
            }
        }
        return $valueOptions;
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

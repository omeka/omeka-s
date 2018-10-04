<?php
namespace Omeka\Form\Element;

use Omeka\Api\Manager as ApiManager;
use Zend\Form\Element\Select;

class UserSelect extends Select
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

    public function getValueOptions()
    {
        $users = $this->getApiManager()->search('users', ['sort_by' => 'name'])->getContent();
        $valueOptions = [];
        foreach ($users as $user) {
            $valueOptions[$user->id()] = sprintf('%s (%s)', $user->name(), $user->email());
        }
        return $valueOptions;
    }
}

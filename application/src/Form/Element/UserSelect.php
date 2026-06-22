<?php
namespace Omeka\Form\Element;

use Omeka\Api\Manager as ApiManager;
use Laminas\Form\Element\Select;

class UserSelect extends Select implements SelectSortInterface
{
    use SelectSortTrait;

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

    public function getValueOptions(): array
    {
        $users = $this->getApiManager()->search('users', ['sort_by' => 'name'])->getContent();
        $valueOptions = [];
        foreach ($users as $user) {
            $valueOptions[$user->id()] = sprintf('%s (%s)', $user->name(), $user->email());
        }
        // Prepend configured value options.
        $prependValueOptions = $this->getOption('prepend_value_options');
        if (is_array($prependValueOptions)) {
            $valueOptions = $prependValueOptions + $valueOptions;
        }
        return $valueOptions;
    }

    public function sortValueOptions(): bool
    {
        // Do not sort because sorting happens in self::getValueOptions()
        return false;
    }

    public function translateValueOptions(): bool
    {
        // Do not translate because value options are user input.
        return false;
    }
}

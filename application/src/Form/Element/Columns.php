<?php
namespace Omeka\Form\Element;

use Laminas\Filter\Callback;
use Laminas\Form\Element;
use Laminas\InputFilter\InputProviderInterface;

class Columns extends Element implements InputProviderInterface
{
    protected $attributes = [
        'class' => 'columns-columns-data',
    ];

    public function getInputSpecification()
    {
        return [
            'required' => false,
            'filters' => [
                // Decode JSON into a PHP array so data can be stored properly.
                new Callback(function ($json) {
                    return json_decode($json, true);
                })
            ],
        ];
    }

    public function getResourceType()
    {
        $resourceType = 'items';
        if (in_array($this->getOption('columns_resource_type'), ['items', 'item_sets', 'media'])) {
            $resourceType = $this->getOption('columns_resource_type');
        }
        return $resourceType;
    }

    public function getUserId()
    {
        $userId = $this->getOption('columns_user_id');
        return is_numeric($userId) ? $userId : null;
    }
}

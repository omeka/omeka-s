<?php
namespace Omeka\Form\Element;

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

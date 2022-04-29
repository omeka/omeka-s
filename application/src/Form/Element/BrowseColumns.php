<?php
namespace Omeka\Form\Element;

use Laminas\Form\Element;
use Laminas\InputFilter\InputProviderInterface;

class BrowseColumns extends Element implements InputProviderInterface
{
    protected $attributes = [
        'class' => 'browse-columns-columns-data',
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
        if (in_array($this->getOption('browse_columns_resource_type'), ['items', 'item_sets', 'media'])) {
            $resourceType = $this->getOption('browse_columns_resource_type');
        }
        return $resourceType;
    }

    public function getUserId()
    {
        $userId = $this->getOption('browse_columns_user_id');
        return is_numeric($userId) ? $userId : null;
    }
}

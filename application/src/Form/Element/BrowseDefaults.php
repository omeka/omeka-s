<?php
namespace Omeka\Form\Element;

use Laminas\Filter\Callback;
use Laminas\Form\Element;
use Laminas\InputFilter\InputProviderInterface;

class BrowseDefaults extends Element implements InputProviderInterface
{
    protected $attributes = [
        'class' => 'browse-defaults',
    ];

    public function getInputSpecification()
    {
        return [
            'required' => false,
            'filters' => [
                // Decode JSON into a PHP array so data can be stored properly.
                new Callback(function ($json) {
                    return json_decode($json, true);
                }),
            ],
        ];
    }

    public function getContext()
    {
        return $this->getOption('browse_defaults_context');
    }

    public function getResourceType()
    {
        return $this->getOption('browse_defaults_resource_type');
    }

    public function getUserId()
    {
        $userId = $this->getOption('browse_defaults_user_id');
        return is_numeric($userId) ? $userId : null;
    }
}

<?php
namespace Omeka\Form\Element;

use Omeka\Api\Manager as ApiManager;
use Omeka\Api\Representation\UserRepresentation;
use Zend\Form\Element\Select;

abstract class AbstractGroupByOwnerSelect extends Select
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

    /**
     * Get the value label from a resource.
     *
     * @param $resource
     * @return string
     */
    abstract public function getValueLabel($resource);

    public function getValueOptions()
    {
        $query = $this->getOption('query');
        if (!is_array($query)) {
            $query = [];
        }

        $response = $this->getApiManager()->search($this->getResourceName(), $query);

        if ($this->getOption('disable_group_by_owner')) {
            // Group alphabetically by resource label without grouping by owner.
            $resources = [];
            foreach ($response->getContent() as $resource) {
                $resources[$this->getValueLabel($resource)][] = $resource->id();
            }
            ksort($resources);
            $valueOptions = [];
            foreach ($resources as $label => $ids) {
                foreach ($ids as $id) {
                    $valueOptions[$id] = $label;
                }
            }
        } else {
            // Group alphabetically by owner email.
            $resourceOwners = [];
            foreach ($response->getContent() as $resource) {
                $owner = $resource->owner();
                $index = $owner ? $owner->email() : null;
                $resourceOwners[$index]['owner'] = $owner;
                $resourceOwners[$index]['resources'][] = $resource;
            }
            ksort($resourceOwners);

            $valueOptions = [];
            foreach ($resourceOwners as $resourceOwner) {
                $options = [];
                foreach ($resourceOwner['resources'] as $resource) {
                    $options[$resource->id()] = $this->getValueLabel($resource);
                    if (!$options) {
                        continue;
                    }
                }
                $owner = $resourceOwner['owner'];
                if ($owner instanceof UserRepresentation) {
                    $label = sprintf('%s (%s)', $owner->name(), $owner->email());
                } else {
                    $label = '[No owner]';
                }
                $valueOptions[] = ['label' => $label, 'options' => $options];
            }
        }

        $prependValueOptions = $this->getOption('prepend_value_options');
        if (is_array($prependValueOptions)) {
            $valueOptions = $prependValueOptions + $valueOptions;
        }
        return $valueOptions;
    }
}

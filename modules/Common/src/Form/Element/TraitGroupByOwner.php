<?php declare(strict_types=1);

namespace Common\Form\Element;

use Omeka\Api\Representation\UserRepresentation;

trait TraitGroupByOwner
{
    use TraitPrependValuesOptions;

    /**
     * Fix prepending value "0" and owner without resource.
     *
     * @see \Omeka\Form\Element\AbstractGroupByOwnerSelect::getValueOptions()
     */
    protected function getValueOptionsFix()
    {
        $query = $this->getOption('query');
        if (!is_array($query)) {
            $query = [];
        }

        $response = $this->getApiManager()->search($this->getResourceName(), $query);

        $valueOptions = [];

        if ($this->getOption('disable_group_by_owner')) {
            // Group alphabetically by resource label without grouping by owner.
            $resources = [];
            foreach ($response->getContent() as $resource) {
                $resources[$this->getValueLabel($resource)][] = $resource->id();
            }
            ksort($resources);
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

            foreach ($resourceOwners as $resourceOwner) {
                if (!$resourceOwner['resources']) {
                    continue;
                }
                $options = [];
                foreach ($resourceOwner['resources'] as $resource) {
                    $options[$resource->id()] = $this->getValueLabel($resource);
                }
                $owner = $resourceOwner['owner'];
                if ($owner instanceof UserRepresentation) {
                    $label = sprintf('%s (%s)', $owner->name(), $owner->email());
                    $index = $owner->id();
                } else {
                    $label = '[No owner]'; // @translate
                    $index = '-0';
                }
                // An index is required to prepend option "0" with array union.
                $valueOptions[$index] = ['label' => $label, 'options' => $options];
            }
        }

        return $this->prependValuesOptions($valueOptions);
    }
}

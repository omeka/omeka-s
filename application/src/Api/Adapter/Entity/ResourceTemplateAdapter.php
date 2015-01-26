<?php
namespace Omeka\Api\Adapter\Entity;

use Doctrine\ORM\QueryBuilder;
use Omeka\Model\Entity\EntityInterface;
use Omeka\Model\Entity\ResourceTemplateProperty;
use Omeka\Stdlib\ErrorStore;

class ResourceTemplateAdapter extends AbstractEntityAdapter
{
    /**
     * {@inheritDoc}
     */
    protected $sortFields = array(
        'label' => 'label',
    );

    /**
     * {@inheritDoc}
     */
    public function getResourceName()
    {
        return 'resource_templates';
    }

    /**
     * {@inheritDoc}
     */
    public function getRepresentationClass()
    {
        return 'Omeka\Api\Representation\Entity\ResourceTemplateRepresentation';
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClass()
    {
        return 'Omeka\Model\Entity\ResourceTemplate';
    }

    /**
     * {@inheritDoc}
     */
    public function sortQuery(QueryBuilder $qb, array $query)
    {
        if (is_string($query['sort_by'])) {
            if ('resource_class_label' == $query['sort_by']) {
                $qb ->leftJoin(
                    $this->getEntityClass() . '.resourceClass',
                    'omeka_order'
                )->orderBy('omeka_order.label', $query['sort_order']);
            } elseif ('item_count' == $query['sort_by']) {
                $this->sortResourceCount($qb, $query, 'Omeka\Model\Entity\Item');
            } else {
                parent::sortQuery($qb, $query);
            }
        }
    }


    /**
     * {@inheritDoc}
     */
    public function validateData(array $data, ErrorStore $errorStore,
        $isManaged
    ){
        // A resource template may not have duplicate properties.
        if (isset($data['o:resource_template_property'])
            && is_array($data['o:resource_template_property'])
        ) {
            $propertyIds = array();
            foreach ($data['o:resource_template_property'] as $resTemPropData) {
                if (!isset($resTemPropData['o:property']['o:id'])) {
                    continue; // skip when no property ID
                }
                $propertyId = $resTemPropData['o:property']['o:id'];
                if (in_array($propertyId, $propertyIds)) {
                    $errorStore->addError('o:property', sprintf(
                        'Attempting to add duplicate property with ID %s',
                        $propertyId
                    ));
                }
                $propertyIds[] = $propertyId;
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function validateEntity(EntityInterface $entity,
        ErrorStore $errorStore, $isManaged
    ) {
        $label = $entity->getLabel();
        if (empty(trim($label))) {
            $errorStore->addError('o:label', 'The label cannot be empty.');
        }
        if (!$this->isUnique($entity, array('label' => $label))) {
            $errorStore->addError('o:label', 'The label is already taken.');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function hydrate(array $data, EntityInterface $entity,
        ErrorStore $errorStore, $isManaged
    ) {
        // o:owner
        $this->hydrateOwner($data, $entity, $isManaged);

        // o:resource_class
        $this->hydrateResourceClass($data, $entity, $isManaged);

        if (isset($data['o:label'])) {
            $entity->setLabel($data['o:label']);
        }

        if (isset($data['o:resource_template_property'])
            && is_array($data['o:resource_template_property'])
        ) {

            // Get a resource template property by property ID.
            $getResTemProp = function ($propertyId, $resTemProps) {
                foreach ($resTemProps as $resTemProp) {
                    if ($propertyId == $resTemProp->getProperty()->getId()) {
                        return $resTemProp;
                    }
                }
                return null;
            };

            $propertyAdapter = $this->getAdapter('properties');
            $resTemProps = $entity->getResourceTemplateProperties();
            $resTemPropsToRetain = array();
            $position = 1;
            foreach ($data['o:resource_template_property'] as $resTemPropData) {

                if (!isset($resTemPropData['o:property']['o:id'])) {
                    continue; // skip when no property ID
                }

                $propertyId = $resTemPropData['o:property']['o:id'];
                $altLabel = isset($resTemPropData['o:alternate_label'])
                    ? $resTemPropData['o:alternate_label'] : null;
                $altComment = isset($resTemPropData['o:alternate_comment'])
                    ? $resTemPropData['o:alternate_comment'] : null;

                // Check whether a passed property is already assigned to this
                // resource template.
                $resTemProp = $getResTemProp($propertyId, $resTemProps);
                if ($resTemProp) {
                    // It is already assigned. Modify the existing entity.
                    $resTemProp->setAlternateLabel($altLabel);
                    $resTemProp->setAlternateComment($altComment);
                } else {
                    // It is not assigned. Add a new resource template property.
                    // No need to explicitly add it to the collection since it
                    // is added implicitly when setting the resource template.
                    $property = $propertyAdapter->findEntity($propertyId);
                    $resTemProp = new ResourceTemplateProperty;
                    $resTemProp->setResourceTemplate($entity);
                    $resTemProp->setProperty($property);
                    $resTemProp->setAlternateLabel($altLabel);
                    $resTemProp->setAlternateComment($altComment);
                }
                // Set the position of the property to its intrinsic order
                // within the passed array.
                $resTemProp->setPosition($position++);
                $resTemPropsToRetain[] = $resTemProp;
            }

            // Remove resource template properties that were not included in the
            // passed data.
            foreach ($resTemProps as $resTemPropId => $resTemProp) {
                if (!in_array($resTemProp, $resTemPropsToRetain)) {
                    $resTemProps->remove($resTemPropId);
                }
            }
        }
    }
}

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
        if (isset($data['o:owner']['o:id'])) {
            $owner = $this->getAdapter('users')
                ->findEntity($data['o:owner']['o:id']);
            $entity->setOwner($owner);
        }
        if (isset($data['o:resource_class']['o:id'])) {
            $resourceClass = null;
            if (is_numeric($data['o:resource_class']['o:id'])) {
                $resourceClass = $this->getAdapter('resource_classes')
                    ->findEntity($data['o:resource_class']['o:id']);
            }
            $entity->setResourceClass($resourceClass);
        }
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

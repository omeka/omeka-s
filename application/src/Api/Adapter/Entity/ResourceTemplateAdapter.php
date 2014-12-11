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
    public function hydrate(array $data, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        if (isset($data['o:owner']['o:id'])) {
            $owner = $this->getAdapter('users')
                ->findEntity($data['o:owner']['o:id']);
            $entity->setOwner($owner);
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

            $resTemProps = $entity->getResourceTemplateProperties();
            $resTemPropsToAdd = array();
            $resTemPropsToRemove = clone $resTemProps;
            foreach ($data['o:resource_template_property'] as $resTemPropData) {

                if (!isset($resTemPropData['o:property']['o:id'])) {
                    continue; // skip when no property ID
                }

                // Check whether a passed property is already assigned to this
                // resource template.
                $resTemProp = $getResTemProp(
                    $resTemPropData['o:property']['o:id'],
                    $resTemProps
                );
                if ($resTemProp) {
                    // It is already assigned. Modify the existing entity.
                    $resTemPropsToRemove->remove($resTemProp->getId());
                    $resTemProp->setAlternateLabel($resTemPropData['o:alternate_label']);
                    $resTemProp->setAlternateComment($resTemPropData['o:alternate_comment']);
                } else {
                    // It is not assigned. Set data to be added.
                    $resTemPropsToAdd[] = $resTemPropData;
                }
            }

            // Add a new resource template property.
            $propertyAdapter = $this->getAdapter('properties');
            foreach ($resTemPropsToAdd as $resTemPropData) {
                $property = $propertyAdapter->findEntity($resTemPropData['o:property']['o:id']);
                $newResTemProp = new ResourceTemplateProperty;
                $newResTemProp->setResourceTemplate($entity);
                $newResTemProp->setProperty($property);
                $newResTemProp->setAlternateLabel($resTemPropData['o:alternate_label']);
                $newResTemProp->setAlternateComment($resTemPropData['o:alternate_comment']);
                $resTemProps->add($newResTemProp);
            }

            // Remove resource template properties that were not included in the
            // passed data.
            foreach ($resTemPropsToRemove as $resTemPropId => $resTemProp) {
                $resTemProps->remove($resTemPropId);
            }
        }
    }
}

<?php
namespace Omeka\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Entity\ResourceTemplateProperty;
use Omeka\Stdlib\ErrorStore;
use Omeka\Stdlib\Message;

class ResourceTemplateAdapter extends AbstractEntityAdapter
{
    /**
     * {@inheritDoc}
     */
    protected $sortFields = [
        'label' => 'label',
    ];

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
        return 'Omeka\Api\Representation\ResourceTemplateRepresentation';
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClass()
    {
        return 'Omeka\Entity\ResourceTemplate';
    }

    /**
     * {@inheritDoc}
     */
    public function sortQuery(QueryBuilder $qb, array $query)
    {
        if (is_string($query['sort_by'])) {
            if ('resource_class_label' == $query['sort_by']) {
                $resourceClassAlias = $this->createAlias();
                $qb->leftJoin(
                    'Omeka\Entity\ResourceTemplate.resourceClass',
                    $resourceClassAlias
                )->addOrderBy("$resourceClassAlias.label", $query['sort_order']);
            } elseif ('item_count' == $query['sort_by']) {
                $this->sortByCount($qb, $query, 'resources', 'Omeka\Entity\Item');
            } else {
                parent::sortQuery($qb, $query);
            }
        }
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['label'])) {
            $qb->andWhere($qb->expr()->eq(
                "Omeka\Entity\ResourceTemplate.label",
                $this->createNamedParameter($qb, $query['label']))
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function validateRequest(Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();

        // A resource template may not have duplicate properties.
        if (isset($data['o:resource_template_property'])
            && is_array($data['o:resource_template_property'])
        ) {
            $propertyIds = [];
            foreach ($data['o:resource_template_property'] as $resTemPropData) {
                if (!isset($resTemPropData['o:property']['o:id'])) {
                    continue; // skip when no property ID
                }
                $propertyId = $resTemPropData['o:property']['o:id'];
                if (in_array($propertyId, $propertyIds)) {
                    $errorStore->addError('o:property', new Message(
                        'Attempting to add duplicate property with ID %s', // @translate
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
        ErrorStore $errorStore
    ) {
        $label = $entity->getLabel();
        if (false == trim($label)) {
            $errorStore->addError('o:label', 'The label cannot be empty.'); // @translate
        }
        if (!$this->isUnique($entity, ['label' => $label])) {
            $errorStore->addError('o:label', 'The label is already taken.'); // @translate
        }
    }

    /**
     * {@inheritDoc}
     */
    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        $data = $request->getContent();
        $this->hydrateOwner($request, $entity);
        $this->hydrateResourceClass($request, $entity);

        if ($this->shouldHydrate($request, 'o:label')) {
            $entity->setLabel($request->getValue('o:label'));
        }

        if ($this->shouldHydrate($request, 'o:resource_template_property')
            && isset($data['o:resource_template_property'])
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
            $resTemPropsToRetain = [];
            $position = 1;
            foreach ($data['o:resource_template_property'] as $resTemPropData) {
                if (!isset($resTemPropData['o:property']['o:id'])) {
                    continue; // skip when no property ID
                }

                $propertyId = $resTemPropData['o:property']['o:id'];
                $altLabel = null;
                if (isset($resTemPropData['o:alternate_label'])
                    && '' !== trim($resTemPropData['o:alternate_label'])
                ) {
                    $altLabel = $resTemPropData['o:alternate_label'];
                }
                $altComment = null;
                if (isset($resTemPropData['o:alternate_comment'])
                    && '' !== trim($resTemPropData['o:alternate_comment'])
                ) {
                    $altComment = $resTemPropData['o:alternate_comment'];
                }
                $dataType = null;
                if (isset($resTemPropData['o:data_type'])
                    && '' !== trim($resTemPropData['o:data_type'])
                ) {
                    $dataType = $resTemPropData['o:data_type'];
                }
                $isRequired = false;
                if (isset($resTemPropData['o:is_required'])) {
                    $isRequired = (bool) $resTemPropData['o:is_required'];
                }

                // Check whether a passed property is already assigned to this
                // resource template.
                $resTemProp = $getResTemProp($propertyId, $resTemProps);
                if (!$resTemProp) {
                    // It is not assigned. Add a new resource template property.
                    // No need to explicitly add it to the collection since it
                    // is added implicitly when setting the resource template.
                    $property = $propertyAdapter->findEntity($propertyId);
                    $resTemProp = new ResourceTemplateProperty;
                    $resTemProp->setResourceTemplate($entity);
                    $resTemProp->setProperty($property);
                    $entity->getResourceTemplateProperties()->add($resTemProp);
                }
                $resTemProp->setAlternateLabel($altLabel);
                $resTemProp->setAlternateComment($altComment);
                $resTemProp->setDataType($dataType);
                $resTemProp->setIsRequired($isRequired);
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

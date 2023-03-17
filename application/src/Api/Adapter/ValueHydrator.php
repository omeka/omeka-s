<?php
namespace Omeka\Api\Adapter;

use Doctrine\Common\Collections\Criteria;
use Omeka\Api\Request;
use Omeka\Entity\Resource;
use Omeka\Entity\Value;
use Omeka\Entity\ValueAnnotation;
use Omeka\Stdlib\ErrorStore;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;

class ValueHydrator
{
    /**
     * Hydrate all resource entity values in a request.
     *
     * All values must contain a property ID (property_id). Values should
     * contain a data type (type). For an invalid or missing type the "literal"
     * type will be used. A value object that is invalid is ignored and not
     * saved.
     *
     * @param Request $request
     * @param Resource $entity
     * @param AbstractResourceEntityAdapter $adapter
     */
    public function hydrate(Request $request, Resource $entity,
        AbstractResourceEntityAdapter $adapter
    ) {
        $isUpdate = Request::UPDATE === $request->getOperation();
        $isPartial = $isUpdate && $request->getOption('isPartial');
        $append = $isPartial && 'append' === $request->getOption('collectionAction');
        $remove = $isPartial && 'remove' === $request->getOption('collectionAction');
        $valueAnnotationAdapter = $adapter->getAdapter('value_annotations');
        $entityManager = $adapter->getEntityManager();

        $representation = $request->getContent();
        $valueCollection = $entity->getValues();

        // During UPDATE requests clear all values of all properties passed via
        // the "clear_property_values" key.
        if ($isUpdate && isset($representation['clear_property_values'])
            && is_array($representation['clear_property_values'])
        ) {
            // Change IDs to entity references to avoid issues with strict Criteria matching.
            $propertyIds = [];
            foreach ($representation['clear_property_values'] as $propertyId) {
                $propertyIds[] = $entityManager->getReference('Omeka\Entity\Property', $propertyId);
            }
            $criteria = Criteria::create()->where(Criteria::expr()->in('property', $propertyIds));
            foreach ($valueCollection->matching($criteria) as $value) {
                $valueCollection->removeElement($value);
            }
        }

        // During UPDATE requests set the value visibility for all properties
        // passed via the "set_value_visibility" key.
        if ($isUpdate && isset($representation['set_value_visibility']['property_id'])
            && is_array($representation['set_value_visibility']['property_id'])
            && isset($representation['set_value_visibility']['is_public'])
        ) {
            // Change IDs to entity references to avoid issues with strict Criteria matching.
            $propertyIds = [];
            foreach ($representation['set_value_visibility']['property_id'] as $propertyId) {
                $propertyIds[] = $entityManager->getReference('Omeka\Entity\Property', $propertyId);
            }
            $criteria = Criteria::create()->where(Criteria::expr()->in('property', $propertyIds));
            foreach ($valueCollection->matching($criteria) as $value) {
                $value->setIsPublic($representation['set_value_visibility']['is_public']);
            }
        }

        if ($remove) {
            // Value hydration does not support removal because individual
            // values have no unambiguous identifiers.
            return;
        }

        $newValues = [];
        $existingValues = $valueCollection->toArray();
        $entityManager = $adapter->getEntityManager();
        $dataTypes = $adapter->getServiceLocator()->get('Omeka\DataTypeManager');

        // Iterate the representation data.
        $valuePassed = false;
        foreach ($representation as $term => $valuesData) {
            if (!is_array($valuesData)) {
                // Ignore invalid values data.
                continue;
            }
            foreach ($valuesData as $valueData) {
                if (!(is_array($valueData) && isset($valueData['property_id']))) {
                    // Ignore invalid value data.
                    continue;
                }
                if (!isset($valueData['type'])) {
                    $valueData['type'] = null;
                }
                try {
                    $dataType = $dataTypes->get($valueData['type']);
                } catch (ServiceNotFoundException $e) {
                    // Ignore an invalid data type.
                    continue;
                }
                if (!$dataType->isValid($valueData)) {
                    // Ignore an invalid value.
                    continue;
                }

                $valuePassed = true;
                $value = current($existingValues);
                if ($value === false || $append) {
                    $value = new Value;
                    $newValues[] = $value;
                } else {
                    // Null out values as we re-use them.
                    $existingValues[key($existingValues)] = null;
                    next($existingValues);
                }

                // Hydrate a single value.
                $value->setResource($entity);
                $value->setType($dataType->getName());
                // If the property_id is "auto", look out to the value's key for
                // a property term.
                $property = 'auto' === $valueData['property_id']
                    ? $adapter->getPropertyByTerm($term)
                    : $entityManager->getReference('Omeka\Entity\Property', $valueData['property_id']);
                $value->setProperty($property);
                if (isset($valueData['is_public'])) {
                    $value->setIsPublic($valueData['is_public']);
                }
                $dataType->hydrate($valueData, $value, $adapter);

                // Hydrate annotation resource.
                $valueAnnotation = $value->getValueAnnotation();
                if (isset($valueData['@annotation']) && is_array($valueData['@annotation']) && $valueData['@annotation']) {
                    // This value has annotation data. Create or update the
                    // value annotation resource.
                    if ($valueAnnotation) {
                        $operation = Request::UPDATE;
                    } else {
                        $operation = Request::CREATE;
                        $valueAnnotation = new ValueAnnotation;
                    }
                    $subrequest = new Request($operation, 'value_annotations');
                    $subrequest->setContent($valueData['@annotation']);
                    $valueAnnotationAdapter->hydrateEntity($subrequest, $valueAnnotation, new ErrorStore);
                    $value->setValueAnnotation($valueAnnotation);
                } elseif ($valueAnnotation) {
                    // This value does not have annotation data. Delete the
                    // value annotation resource.
                    $value->setValueAnnotation(null);
                }
            }
        }

        // Remove any values that weren't reused. This step should only happen
        // during an UPDATE, or during a PATCH (isPartial=true) -if- it's a
        // default collection (collectionAction=replace) -and- at least one
        // value was passed in the request.
        if (!$isPartial || (!$append && $valuePassed)) {
            foreach ($existingValues as $key => $existingValue) {
                if ($existingValue !== null) {
                    $valueCollection->remove($key);
                }
            }
        }

        // Add any new values that had to be created.
        foreach ($newValues as $newValue) {
            $valueCollection->add($newValue);
        }
    }
}

<?php
namespace Omeka\Api\Adapter;

use Doctrine\Common\Collections\Criteria;
use Omeka\Api\Request;
use Omeka\Entity\Property;
use Omeka\Entity\Resource;
use Omeka\Entity\Value;
use Zend\ServiceManager\Exception\ServiceNotFoundException;

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

        $representation = $request->getContent();
        $valueCollection = $entity->getValues();

        // During UPDATE requests clear all values of all properties passed via
        // the "clear_property_values" key.
        if ($isUpdate && isset($representation['clear_property_values'])
            && is_array($representation['clear_property_values'])
        ) {
            $criteria = Criteria::create()->where(
                Criteria::expr()->in('property', $representation['clear_property_values']
            ));
            foreach ($valueCollection->matching($criteria) as $value) {
                $valueCollection->removeElement($value);
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

        // Iterate the representation data. Note that we ignore terms.
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
                $value->setProperty($entityManager->getReference(
                    'Omeka\Entity\Property',
                    $valueData['property_id']
                ));
                $dataType->hydrate($valueData, $value, $adapter);
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

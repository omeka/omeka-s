<?php
namespace Omeka\Api\Adapter;

use Omeka\Entity\Property;
use Omeka\Entity\Resource;
use Omeka\Entity\Value;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\Hydrator\HydrationInterface;

class ValueHydrator implements HydrationInterface
{
    /**
     * @var AbstractEntityAdapter
     */
    protected $adapter;

    /**
     * @param AbstractEntityAdapter $adapter
     */
    public function __construct(AbstractEntityAdapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Hydrate all value objects within a JSON-LD node object.
     *
     * The node object represents a resource entity. All values must contain a
     * property ID (property_id). All values should conatin a data type (type).
     * For an invalid or missing type the "literal" type will be used. A value
     * object that is invalid is ignored and not saved.
     *
     * @param array $nodeObject A JSON-LD node object representing a resource
     * @param Resource $resource The owning resource entity instance
     * @param bool $append Whether to simply append instead of replacing
     *  existing values
     */
    public function hydrate(array $nodeObject, $resource, $append = false)
    {
        $newValues = [];
        $valueCollection = $resource->getValues();
        $existingValues = $valueCollection->toArray();
        $dataTypes = $this->adapter->getServiceLocator()->get('Omeka\DataTypeManager');

        // Iterate all properties in a node object. Note that we ignore terms.
        foreach ($nodeObject as $property => $valueObjects) {
            // Value objects must be contained in lists
            if (!is_array($valueObjects)) {
                continue;
            }
            // Iterate a node object list
            foreach ($valueObjects as $valueObject) {

                // Value objects must be lists and contain a property ID.
                if (!(is_array($valueObject) && isset($valueObject['property_id']))) {
                    continue;
                }

                if (!isset($valueObject['type'])) {
                    $valueObject['type'] = null;
                }
                try {
                    $dataType = $dataTypes->get($valueObject['type']);
                } catch (ServiceNotFoundException $e) {
                    // Ignore an invalid data type.
                    continue;
                }
                if (!$dataType->isValid($valueObject)) {
                    // Ignore an invalid value.
                    continue;
                }

                $value = current($existingValues);
                if ($value === false || $append) {
                    $value = new Value;
                    $newValues[] = $value;
                } else {
                    // Null out values as we re-use them
                    $existingValues[key($existingValues)] = null;
                    next($existingValues);
                }

                // Hydrate a single JSON-LD value object
                $property = $this->adapter->getEntityManager()->getReference(
                    'Omeka\Entity\Property',
                    $valueObject['property_id']
                );
                $value->setType($dataType->getName());
                $value->setResource($resource);
                $value->setProperty($property);
                $dataType->hydrate($valueObject, $value, $this->adapter);
            }
        }

        // Remove any values that weren't reused
        if (!$append) {
            foreach ($existingValues as $key => $existingValue) {
                if ($existingValue !== null) {
                    $valueCollection->remove($key);
                }
            }
        }

        // Add any new values that had to be created
        foreach ($newValues as $newValue) {
            $valueCollection->add($newValue);
        }
    }
}

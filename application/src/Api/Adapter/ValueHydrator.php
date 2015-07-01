<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Exception;
use Omeka\Entity\Property;
use Omeka\Entity\Media;
use Omeka\Entity\Resource;
use Omeka\Entity\Value;
use Zend\Stdlib\Hydrator\HydrationInterface;

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
     * The node object represents a resource entity.
     *
     * Parses value objects according to the existence of certain properties, in
     * order of priority:
     *
     * - property_id: all value types must contain a property ID
     * - @value: hydrate a literal
     * - value_resource_id: hydrate a resource value
     * - @id: hydrate a URI value
     *
     * A value object that contains none of the above combinations is ignored.
     *
     * @param array $nodeObject A JSON-LD node object representing a resource
     * @param Resource $resource The owning resource entity instance
     * @param boolean $append Whether to simply append instead of replacing
     *  existing values
     */
    public function hydrate(array $nodeObject, $resource, $append = false)
    {
        $newValues = array();
        $valueCollection = $resource->getValues();
        $existingValues = $valueCollection->toArray();

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

                // Value objects must be mapped to a hydrate method.
                $hydrateMethod = $this->getHydrateMethod($valueObject);
                if (!$hydrateMethod) {
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
                $value->setResource($resource);
                $value->setProperty($property);
                $this->$hydrateMethod($valueObject, $value);
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

    /**
     * Get a value object's hydrate method by checking against expected formats.
     *
     * Returns null if the value object is an invalid format.
     *
     * @param array $valueObject
     * @return null|string
     */
    protected function getHydrateMethod(array $valueObject)
    {
        if (isset($valueObject['@value'])
            && is_string($valueObject['@value'])
            && '' !== trim($valueObject['@value'])
        ) {
            return 'hydrateLiteral';
        }
        if (isset($valueObject['@id'])
            && is_string($valueObject['@id'])
            && '' !== trim($valueObject['@id'])
        ) {
             return 'hydrateUri';
        }
        if (isset($valueObject['value_resource_id'])
            && is_numeric($valueObject['value_resource_id'])
        ) {
             return 'hydrateResource';
        }
        return null; // invalid value object
    }

    /**
     * Hydrate a literal value
     *
     * @param array $valueObject
     * @param Value $value
     */
    protected function hydrateLiteral(array $valueObject, Value $value)
    {
        $value->setType(Value::TYPE_LITERAL);
        $value->setValue($valueObject['@value']);
        if (isset($valueObject['@language'])) {
            $value->setLang($valueObject['@language']);
        } else {
            $value->setLang(null); // set default
        }
        $value->setUriLabel(null); // set default
        $value->setValueResource(null); // set default
    }

    /**
     * Hydrate a resource value
     *
     * @param array $valueObject
     * @param Value $value
     */
    protected function hydrateResource(array $valueObject, Value $value)
    {
        $value->setType(Value::TYPE_RESOURCE);
        $value->setValue(null); // set default
        $value->setLang(null); // set default
        $value->setUriLabel(null); // set default
        $valueResource = $this->adapter->getEntityManager()->find(
            'Omeka\Entity\Resource',
            $valueObject['value_resource_id']
        );
        if (null === $valueResource) {
            throw new Exception\NotFoundException(sprintf(
                $this->adapter->getTranslator()->translate('Resource not found with id %s.'),
                $valueObject['value_resource_id']
            ));
        }
        if ($valueResource instanceof Media) {
            $translator = $this->adapter->getTranslator();
            $exception = new Exception\ValidationException;
            $exception->getErrorStore()->addError(
                'value', $translator->translate('A value resource cannot be Media.')
            );
            throw $exception;
        }
        $value->setValueResource($valueResource);
    }

    /**
     * Hydrate a URI value
     *
     * @param array $valueObject
     * @param Value $value
     */
    protected function hydrateUri(array $valueObject, Value $value)
    {
        $value->setType(Value::TYPE_URI);
        $value->setValue($valueObject['@id']);
        if (isset($valueObject['o:uri_label'])) {
            $value->setUriLabel($valueObject['o:uri_label']);
        } else {
            $value->setUriLabel(null); // set default
        }
        $value->setLang(null); // set default
        $value->setValueResource(null); // set default
    }
}

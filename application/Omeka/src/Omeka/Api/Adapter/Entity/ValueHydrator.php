<?php
namespace Omeka\Api\Adapter\Entity;

use Omeka\Model\Entity\Property;
use Omeka\Model\Entity\Resource;
use Omeka\Model\Entity\Value;
use Zend\Stdlib\Hydrator\HydratorInterface;

class ValueHydrator implements HydratorInterface
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
     * @param array $nodeObject A JSON-LD node object representing a resource
     * @param Resource $resource The owning resource entity instance
     */
    public function hydrate(array $nodeObject, $resource)
    {
        // Iterate all properties in a node object. Note that we ignore terms.
        foreach ($nodeObject as $property => $valueObjects) {
            // Value objects must be contained in lists
            if (!is_array($valueObjects)) {
                continue;
            }
            // Iterate a node object list
            foreach ($valueObjects as $valueObject) {
                // Value objects must be lists
                if (!is_array($valueObject)) {
                    continue;
                }
                $this->hydrateValue($valueObject, $resource);
            }
        }
    }

    /**
     * Extract all values of a resource.
     *
     * @param Resource $resource
     * @return array JSON-LD formatted
     */
    public function extract($resource)
    {
        $context = array();
        $valueObjects = array();
        foreach ($resource->getValues() as $value) {

            $property = $value->getProperty();
            $vocabulary = $property->getVocabulary();

            $prefix = $vocabulary->getPrefix();
            $suffix = $property->getLocalName();
            $term = "$prefix:$suffix";

            if (!array_key_exists($prefix, $context)) {
                $context[$prefix] = array(
                    '@id' => $vocabulary->getNamespaceUri(),
                    'vocabulary_id' => $vocabulary->getId(),
                    'vocabulary_label' => $vocabulary->getLabel(),
                );
            }

            $valueObjects[$term][] = $this->extractValue($value, $property);
        }

        $valueObjects['@context'] = $context;
        return $valueObjects;
    }

    /**
     * Hydrate a single JSON-LD value object.
     *
     * Parses the value object according to the existence of certain properties,
     * in order of priority:
     *
     * - value_id & delete=true: remove the value
     * - value_id & @value: modify a literal
     * - value_id & value_resource_id: modify a resource value
     * - value_id & @id: modify a URI value
     * - property_id & @value: persist a literal
     * - property_id & value_resource_id: persist a resource value
     * - property_id & @id: persist a URI value
     *
     * A value object that contains none of the above combinations is ignored.
     *
     * @param array $valueObject A (potential) JSON-LD value object
     * @param Resource $resource The owning resource entity instance
     */
    public function hydrateValue(array $valueObject, Resource $resource)
    {
        if (isset($valueObject['value_id'])) {
            // Modify an existing value
            $value = $this->adapter->getEntityManager()->getReference(
                'Omeka\Model\Entity\Value',
                $valueObject['value_id']
            );
            if (isset($valueObject['delete']) && true === $valueObject['delete']) {
                $this->remove($value);
            } elseif (array_key_exists('@value', $valueObject)) {
                $this->modifyLiteral($valueObject, $value);
            } elseif (array_key_exists('value_resource_id', $valueObject)) {
                $this->modifyResource($valueObject, $value);
            } elseif (array_key_exists('@id', $valueObject)) {
                $this->modifyUri($valueObject, $value);
            }
        } elseif (isset($valueObject['property_id'])) {
            // Persist a new value
            $property = $this->adapter->getEntityManager()->getReference(
                'Omeka\Model\Entity\Property',
                $valueObject['property_id']
            );
            if (array_key_exists('@value', $valueObject)) {
                $this->persistLiteral($valueObject, $property, $resource);
            } elseif (array_key_exists('value_resource_id', $valueObject)) {
                $this->persistResource($valueObject, $property, $resource);
            } elseif (array_key_exists('@id', $valueObject)) {
                $this->persistUri($valueObject, $property, $resource);
            }
        }
    }

    /**
     * Extract a single value entity.
     *
     * @param Value $value
     * @return array JSON-LD value object
     */
    public function extractValue(Value $value, Property $property) {
        $valueObject = array();
        switch ($value->getType()) {
            case Value::TYPE_RESOURCE:
                $valueResource = $value->getValueResource();
                $valueResourceAdapter = $this->adapter->getAdapter(
                    $valueResource->getResourceName()
                );
                $valueObject['@id'] = $valueResourceAdapter->getApiUrl($valueResource);
                $valueObject['value_resource_id'] = $valueResource->getId();
                break;
            case Value::TYPE_URI:
                $valueObject['@id'] = $value->getValue();
                break;
            case Value::TYPE_LITERAL:
            default:
                $valueObject['@value'] = $value->getValue();
                if ($value->getLang()) {
                    $valueObject['@language'] = $value->getLang();
                }
                $valueObject['is_html'] = $value->getIsHtml();
                break;
        }
        $valueObject['value_id'] = $value->getId();
        $valueObject['property_id'] = $property->getId();
        $valueObject['property_label'] = $property->getLabel();
        return $valueObject;
    }

    /**
     * Delete a value
     *
     * @param Value $value
     */
    protected function remove(Value $value)
    {
        $this->adapter->getEntityManager()->remove($value);
    }

    /**
     * Update a literal value
     *
     * @param array $valueObject
     * @param Value $value
     */
    protected function modifyLiteral(array $valueObject, Value $value)
    {
        $value->setType(Value::TYPE_LITERAL);
        $value->setValue($valueObject['@value']);
        if (isset($valueObject['@language'])) {
            $value->setLang($valueObject['@language']);
        } else {
            $value->setLang(null); // set default
        }
        if (isset($valueObject['is_html']) && true === $valueObject['is_html']) {
            $value->setIsHtml(true);
        } else {
            $value->setIsHtml(false); // set default
        }
        $value->setValueResource(null); // set default
    }

    /**
     * Update a resource value
     *
     * @param array $valueObject
     * @param Value $value
     */
    protected function modifyResource(array $valueObject, Value $value)
    {
        $value->setType(Value::TYPE_RESOURCE);
        $value->setValue(null); // set default
        $value->setLang(null); // set default
        $value->setIsHtml(false); // set default
        $valueResource = $this->adapter->getEntityManager()->getReference(
            'Omeka\Model\Entity\Resource',
            $valueObject['value_resource_id']
        );
        $value->setValueResource($valueResource);
    }

    /**
     * Update a URI value
     *
     * @param array $valueObject
     * @param Value $value
     */
    protected function modifyUri(array $valueObject, Value $value)
    {
        $value->setType(Value::TYPE_URI);
        $value->setValue($valueObject['@id']);
        $value->setLang(null); // set default
        $value->setIsHtml(false); // set default
        $value->setValueResource(null); // set default
    }

    /**
     * Create a literal value
     *
     * @param array $valueObject
     * @param Value $value
     * @param Resource $resource
     */
    protected function persistLiteral(array $valueObject, Property $property,
        Resource $resource
    ) {
        $value = new Value;
        $value->setResource($resource);
        $value->setProperty($property);
        $value->setType(Value::TYPE_LITERAL);
        $value->setValue($valueObject['@value']);
        if (isset($valueObject['@language'])) {
            $value->setLang($valueObject['@language']);
        }
        if (isset($valueObject['is_html'])) {
            $value->setIsHtml($valueObject['is_html']);
        }

        $this->adapter->getEntityManager()->persist($value);
    }

    /**
     * Create a resource value
     *
     * @param array $valueObject
     * @param Value $value
     * @param Resource $resource
     */
    protected function persistResource(array $valueObject, Property $property,
        Resource $resource
    ) {
        $value = new Value;
        $value->setResource($resource);
        $value->setProperty($property);
        $value->setType(Value::TYPE_RESOURCE);
        $valueResource = $this->adapter->getEntityManager()->getReference(
            'Omeka\Model\Entity\Resource',
            $valueObject['value_resource_id']
        );
        $value->setValueResource($valueResource);

        $this->adapter->getEntityManager()->persist($value);
    }

    /**
     * Create a URI value
     *
     * @param array $valueObject
     * @param Value $value
     * @param Resource $resource
     */
    protected function persistUri(array $valueObject, Property $property,
        Resource $resource
    ) {
        $value = new Value;
        $value->setResource($resource);
        $value->setProperty($property);
        $value->setType(Value::TYPE_URI);
        $value->setValue($valueObject['@id']);

        $this->adapter->getEntityManager()->persist($value);
    }
}

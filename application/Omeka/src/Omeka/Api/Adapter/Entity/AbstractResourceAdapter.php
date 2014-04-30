<?php
namespace Omeka\Api\Adapter\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Omeka\Model\Entity\Resource;
use Omeka\Model\Entity\Value;

/**
 * Abstract resource entity API adapter.
 *
 * Provides extra functionality for extracting and processing values for
 * entities that utilize Omeka's RDF data model (i.e. those that extend
 * \Omeka\Model\Entity\Resource).
 */
abstract class AbstractResourceAdapter extends AbstractEntityAdapter
{
    /**
     * Process value objects within a JSON-LD node object.
     *
     * @param array $nodeObject
     * @param Resource $resource
     */
    public function processNodeObject(array $nodeObject, Resource $resource)
    {
        // Iterate all properties in a node object. Note that we ignore terms.
        foreach ($nodeObject as $property => $valueObjects) {
            // Value objects must be lists
            if (!is_array($valueObjects)) {
                continue;
            }
            // Iterate a node object list
            foreach ($valueObjects as $valueObject) {
                $this->processValueObject($valueObject, $resource);
            }
        }
    }

    public function processValueObject(array $valueObject, Resource $resource)
    {
        if (isset($valueObject['value_id'])) {
            $value = $entityManager->getReference(
                'Omeka\Model\Entity\Value',
                $valueObject['value_id']
            );
            if (isset($valueObject['delete']) && true === $valueObject['delete']) {
                $this->deleteValue($value);
            } elseif (array_key_exists('@value', $valueObject)) {
                $this->updateValueLiteral($valueObject, $value);
            } elseif (array_key_exists('@id', $valueObject)) {
                if (isset($valueObject['value_resource_id'])) {
                    $this->updateValueResource($valueObject, $value);
                } else {
                    $this->updateValueUri($valueObject, $value);
                }
            }
        } elseif (isset($valueObject['property_id'])) {
            $property = $entityManager->getReference(
                'Omeka\Model\Entity\Property',
                $valueObject['property_id']
            );
            if (array_key_exists('@value', $valueObject)) {
                $this->createValueLiteral($valueObject, $property, $resource);
            } elseif (array_key_exists('@id', $valueObject)) {
                if (isset($valueObject['value_resource_id'])) {
                    $this->createValueResource($valueObject, $property, $resource);
                } else {
                    $this->createValueUri($valueObject, $property, $resource);
                }
            }
        }
    }

    protected function deleteValue(Value $value)
    {
        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');
        $entityManager->remove($value);
    }

    protected function updateValueLiteral(array $valueObject, Value $value)
    {
        $value->setType(Value::TYPE_LITERAL);
        $value->setValue($valueObject['@value']);
        if (isset($valueObject['@language'])) {
            $value->setLang($valueObject['@language']);
        } else {
            $value->setLang(null); // set default
        }
        if (isset($valueObject['is_html'])) {
            $value->setIsHtml($valueObject['is_html']);
        } else {
            $value->setIsHtml(false); // set default
        }
        $value->setValueResource(null); // set default
    }

    protected function updateValueResource(array $valueObject, Value $value)
    {
        $value->setType(Value::TYPE_RESOURCE);
        $value->setValue(null); // set default
        $value->setLang(null); // set default
        $value->setIsHtml(false); // set default
        $valueResource = $entityManager->getReference(
            'Omeka\Model\Entity\Resource',
            $valueObject['value_resource_id']
        );
        $value->setValueResource($valueResource);
    }

    protected function updateValueUri(array $valueObject, Value $value)
    {
        $value->setType(Value::TYPE_URI);
        $value->setValue($valueObject['@id']);
        $value->setLang(null); // set default
        $value->setIsHtml(false); // set default
        $value->setValueResource(null); // set default
    }

    protected function createValueLiteral(
        array $valueObject,
        Property $property,
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

        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');
        $entityManager->persist($value);
    }

    protected function createValueResource(
        array $valueObject,
        Property $property,
        Resource $resource
    ) {
        $value = new Value;
        $value->setResource($resource);
        $value->setProperty($property);
        $value->setType(Value::TYPE_RESOURCE);
        $valueResource = $entityManager->getReference(
            'Omeka\Model\Entity\Resource',
            $valueObject['value_resource_id']
        );
        $value->setValueResource($valueResource);

        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');
        $entityManager->persist($value);
    }

    protected function createValueUri(
        array $valueObject,
        Property $property,
        Resource $resource
    ) {
        $value = new Value;
        $value->setResource($resource);
        $value->setProperty($property);
        $value->setType(Value::TYPE_URI);
        $value->setValue($valueObject['@id']);

        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');
        $entityManager->persist($value);
    }
}

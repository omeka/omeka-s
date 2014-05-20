<?php
namespace Omeka\Api\Representation\Entity;

use Omeka\Api\Representation\ValueRepresentation;
use Omeka\Model\Entity\Resource as ResourceEntity;
use Omeka\Model\Entity\Value as ValueEntity;
use Omeka\Model\Entity\Vocabulary as VocabularyEntity;

abstract class AbstractResourceEntity extends Representation
{
    /**
     * @var array
     */
    protected $valueObjects = array();

    /**
     * @var array
     */
    protected $contextObject = array();

    /**
     * Get all JSON-LD value objects of this resource.
     *
     * @return array
     */
    public function getValueObjects()
    {
        if (empty($this->valueObjects)) {
            $this->setValueObjects();
        }
        return $this->valueObjects;
    }

    /**
     * Get the requested array representation of the value or values.
     *
     * @param string $term The vocabulary prefix and property local_name in the
     * form: "prefix:local_name"
     * @param array $options
     *   - type: (default: "literal") the type of value
     *   - default: (default: null) the default value if the value is not found
     *   - all: (default: false) if true, return all values
     * @return mixed
     */
    public function getValue($term, array $options = array())
    {
        if (!isset($options['type'])) {
            $options['type'] = ValueEntity::TYPE_LITERAL;
        }
        if (!isset($options['default'])) {
            $options['default'] = null;
        }
        if (!isset($options['all'])) {
            $options['all'] = false;
        }

        $valueObjects = $this->getValueObjects();
        if (!array_key_exists($term, $valueObjects)) {
            return $options['default'];
        }

        $values = array();
        foreach ($valueObjects[$term] as $valueObject) {
            $value = $valueObject->toArray();
            $valueType = $valueObject->getValueType();
            if ($options['type'] == $valueType) {
                $values[] = $value;
            }
            if (!$options['all']) {
                break;
            }
        }

        if (!$options['all'] && !empty($values)) {
            $values = $values[0];
        }
        return $values;
    }

    /**
     * Set all JSON-LD value objects of this resource.
     */
    protected function setValueObjects()
    {
        foreach ($this->getData()->getValues() as $value) {
            $property = $value->getProperty();
            $vocabulary = $property->getVocabulary();

            $prefix = $vocabulary->getPrefix();
            $suffix = $property->getLocalName();
            $term = "$prefix:$suffix";

            $this->addVocabularyToContext($vocabulary);
            $this->valueObjects[$term][] = new ValueRepresentation(
                $value, $this->getServiceLocator()
            );
        }
    }

    public function getContextObject()
    {
        return $this->contextObject;
    }

    /**
     * Add a vocabulary term definition to the JSON-LD context object.
     *
     * @param VocabularyEntity $vocabulary
     */
    protected function addVocabularyToContext(VocabularyEntity $vocabulary)
    {
        $prefix = $vocabulary->getPrefix();
        if (array_key_exists($prefix, $this->contextObject)) {
            return;
        }
        $this->contextObject[$prefix] = array(
            '@id' => $vocabulary->getNamespaceUri(),
            'vocabulary_id' => $vocabulary->getId(),
            'vocabulary_label' => $vocabulary->getLabel(),
        );
    }

    /**
     * Get the merged JSON-LD representation of this resource.
     *
     * @param ResourceEntity $resource The resource entity
     * @param array $representation Data specific to this resource
     * @return array
     */
    protected function getRepresentation(ResourceEntity $resource, array $representation)
    {
        $valueObjects = $this->getValueObjects();
        $contextObject = array('@context' => $this->getContextObject());
        return array_merge($contextObject, $representation, $valueObjects);
    }
}

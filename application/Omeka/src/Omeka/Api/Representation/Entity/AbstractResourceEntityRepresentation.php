<?php
namespace Omeka\Api\Representation\Entity;

use Omeka\Api\Representation\ValueRepresentation;
use Omeka\Model\Entity\Resource;
use Omeka\Model\Entity\Value;
use Omeka\Model\Entity\Vocabulary;

/**
 * Abstract resource entity representation.
 *
 * Provides functionality for entities that extend Omeka\Model\Entity\Resource.
 */
abstract class AbstractResourceEntityRepresentation extends AbstractEntityRepresentation
{
    /**
     * @var array
     */
    protected $valueRepresentations = array();

    /**
     * @var array
     */
    protected $contextObject = array();

    /**
     * Serialize the resource entity to a JSON-LD compatible format.
     *
     * @return array
     */
    abstract public function jsonSerializeResource();

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        $valueRepresentations = $this->getValueRepresentations();
        $contextObject = array('@context' => $this->getContextObject());
        $resource = $this->jsonSerializeResource();
        return array_merge($contextObject, $resource, $valueRepresentations);
    }

    /**
     * @var array
     */
    public function validateData($data)
    {
        if (!$data instanceof Resource) {
            throw new Exception\InvalidArgumentException(
                $this->getTranslator()->translate(sprintf(
                    'Invalid data sent to %s.', get_called_class()
                ))
            );
        }
    }

    /**
     * Get all value representations of this resource.
     *
     * @return array
     */
    public function getValueRepresentations()
    {
        if (empty($this->valueRepresentations)) {
            $this->setValueRepresentations();
        }
        return $this->valueRepresentations;
    }

    /**
     * Set all JSON-LD value objects of this resource.
     */
    protected function setValueRepresentations()
    {
        foreach ($this->getData()->getValues() as $value) {
            $property = $value->getProperty();
            $vocabulary = $property->getVocabulary();

            $prefix = $vocabulary->getPrefix();
            $suffix = $property->getLocalName();
            $term = "$prefix:$suffix";

            $this->addVocabularyToContext($vocabulary);
            $this->valueRepresentations[$term][] = new ValueRepresentation(
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
     * @param Vocabulary $vocabulary
     */
    protected function addVocabularyToContext(Vocabulary $vocabulary)
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
     * Get value representations.
     *
     * @param string $term The prefix:local_part
     * @param array $options
     * - type: (null) Get values of this type only. Valid types are "literal",
     *   "uri", and "resource". Returns all types by default.
     * - all: (false) If true, returns all values that match criteria. If false,
     *   returns the first matching value.
     * - default: (null) Default value if no values match criteria. Returns null
     *   by default.
     * - lang: (null) Get values of this language only. Returns values of all
     *   languages by default.
     * @return RepresentationInterface|mixed
     */
    public function getValue($term, array $options = array())
    {
        // Set defaults.
        if (!isset($options['type'])) {
            $options['type'] = null;
        }
        if (!isset($options['all'])) {
            $options['all'] = false;
        }
        if (!isset($options['default'])) {
            $options['default'] = null;
        }
        if (!isset($options['lang'])) {
            $options['lang'] = null;
        }

        $valueReps = $this->getValueRepresentations();
        if (!array_key_exists($term, $valueReps)) {
            return $options['default'];
        }

        // Match only the representations that fit all the criteria.
        $matchingReps = array();
        foreach ($valueReps[$term] as $valueRep) {
            if (!is_null($options['type'])
                && $valueRep->getType() !== $options['type']
            ) {
                continue;
            }
            if (!is_null($options['lang'])
                && $valueRep->getLang() !== $options['lang']
            ) {
                continue;
            }
            $matchingReps[] = $valueRep;
        }

        if (!count($matchingReps)) {
            return $options['default'];
        }

        return $options['all'] ? $matchingReps : $matchingReps[0];
    }
}

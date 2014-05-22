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
     * Get the merged JSON-LD representation of this resource.
     *
     * @param Resource $resource The resource entity
     * @param array $representation Data specific to this resource
     * @return array
     */
    protected function getRepresentation(Resource $resource, array $representation)
    {
        $valueRepresentations = $this->getValueRepresentations();
        $contextObject = array('@context' => $this->getContextObject());
        return array_merge($contextObject, $representation, $valueRepresentations);
    }
}

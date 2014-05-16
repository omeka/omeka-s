<?php
namespace Omeka\Api\Representation;

use Omeka\Model\Entity\Resource as ResourceEntity;
use Omeka\Model\Entity\Value as ValueEntity;
use Omeka\Model\Entity\Vocabulary as VocabularyEntity;

abstract class AbstractResourceEntity extends EntityRepresentation
{
    /**
     * @var array
     */
    protected $valueObjects = array();

    /**
     * @var array
     */
    protected $contextObject = array('@context' => array());

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
            $this->valueObjects[$term][] = new Value($value, $this->getServiceLocator());
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
        if (array_key_exists($prefix, $this->contextObject['@context'])) {
            return;
        }
        $this->contextObject['@context'][$prefix] = array(
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
        return array_merge(
            $this->getContextObject(),
            $representation,
            $valueObjects
        );
    }
}

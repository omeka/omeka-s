<?php
namespace Omeka\Api\Representation;

class VocabularyRepresentation extends AbstractEntityRepresentation
{
    public function getControllerName()
    {
        return 'vocabulary';
    }

    public function getJsonLdType()
    {
        return 'o:Vocabulary';
    }

    public function getJsonLd()
    {
        $owner = null;
        if ($this->owner()) {
            $owner = $this->owner()->getReference();
        }
        return [
            'o:namespace_uri' => $this->namespaceUri(),
            'o:prefix' => $this->prefix(),
            'o:label' => $this->label(),
            'o:comment' => $this->comment(),
            'o:owner' => $owner,
        ];
    }

    /**
     * Return the reading options the vocabulary's file was read with, if
     * known. They aren't part of the JSON-LD.
     *
     * @see \Omeka\Stdlib\RdfImporter::READING_OPTIONS
     * @return array|null
     */
    public function importOptions()
    {
        return $this->resource->getImportOptions();
    }

    /**
     * Check whether this vocabulary is permanent (cannot be deleted).
     *
     * Dublin Core and Dublin Core Type vocabularies are integral parts of the
     * software and should not be deleted.
     *
     * @return bool
     */
    public function isPermanent()
    {
        return in_array($this->prefix(), ['dcterms', 'dctype']);
    }

    /**
     * Return the vocabulary prefix.
     *
     * @return string
     */
    public function prefix()
    {
        return $this->resource->getPrefix();
    }

    /**
     * Return the vocabulary namespace URI.
     *
     * @return string
     */
    public function namespaceUri()
    {
        return $this->resource->getNamespaceUri();
    }

    /**
     * Return the vocabulary label.
     *
     * @return string
     */
    public function label()
    {
        return $this->resource->getLabel();
    }

    /**
     * Return the vocabulary comment.
     *
     * @return string
     */
    public function comment()
    {
        return $this->resource->getComment();
    }

    public function owner()
    {
        return $this->getAdapter('users')
            ->getRepresentation($this->resource->getOwner());
    }

    /**
     * Return property members.
     *
     * @return array
     */
    public function properties()
    {
        $properties = [];
        $propertyAdapter = $this->getAdapter('properties');
        foreach ($this->resource->getProperties() as $propertyEntity) {
            $properties[] = $propertyAdapter->getRepresentation($propertyEntity);
        }
        return $properties;
    }

    /**
     * Return resource class members.
     *
     * @return array
     */
    public function resourceClasses()
    {
        $resourceClasses = [];
        $resourceClassAdapter = $this->getAdapter('resource_classes');
        foreach ($this->resource->getResourceClasses() as $resourceClass) {
            $resourceClasses[] = $resourceClassAdapter->getRepresentation($resourceClass);
        }
        return $resourceClasses;
    }

    /**
     * Get this vocabulary's property count.
     *
     * @return int
     */
    public function propertyCount()
    {
        return count($this->resource->getProperties());
    }

    /**
     * Get this vocabulary's resource class count.
     *
     * @return int
     */
    public function resourceClassCount()
    {
        return count($this->resource->getResourceClasses());
    }

    /**
     * Get the number of values of this vocabulary's properties.
     *
     * @return int
     */
    public function valueCount()
    {
        return $this->countByTerm('Omeka\Entity\Value', 'property');
    }

    /**
     * Get the number of resource template properties of this vocabulary.
     *
     * @return int
     */
    public function resourceTemplatePropertyCount()
    {
        return $this->countByTerm('Omeka\Entity\ResourceTemplateProperty', 'property');
    }

    /**
     * Get the number of resources whose class is one of this vocabulary's.
     *
     * @return int
     */
    public function resourceCount()
    {
        return $this->countByTerm('Omeka\Entity\Resource', 'resourceClass');
    }

    /**
     * Count the entities of a class whose term is one of this vocabulary's.
     *
     * @param string $entityClass
     * @param string $termField The entity's property or resource class
     * @return int
     */
    protected function countByTerm($entityClass, $termField)
    {
        return (int) $this->getServiceLocator()->get('Omeka\EntityManager')
            ->createQuery(sprintf('SELECT COUNT(e.id) FROM %s e JOIN e.%s t WHERE t.vocabulary = :vocabulary', $entityClass, $termField))
            ->setParameter('vocabulary', $this->id())
            ->getSingleScalarResult();
    }
}

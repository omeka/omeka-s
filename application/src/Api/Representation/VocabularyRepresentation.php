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
            'o:import_options' => $this->importOptions(),
        ];
    }

    /**
     * Return the import options the vocabulary's file was read with, if
     * known.
     *
     * @see \Omeka\Entity\Vocabulary::IMPORT_OPTIONS
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
     * Check whether the current user may change this vocabulary, by editing
     * it or updating its classes and properties: one the user may update,
     * and not a permanent one, which no one may change.
     *
     * @return bool
     */
    public function userMayUpdate()
    {
        return !$this->isPermanent() && $this->userIsAllowed('update');
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
        return $this->getAdapter()->getValueCount($this->resource);
    }

    /**
     * Get the number of resource templates that use any of this vocabulary's
     * properties.
     *
     * @return int
     */
    public function resourceTemplateCount()
    {
        return $this->getAdapter()->getResourceTemplateCount($this->resource);
    }

    /**
     * Get the number of resources whose class is one of this vocabulary's.
     *
     * @return int
     */
    public function resourceWithClassCount()
    {
        return $this->getAdapter()->getResourceWithClassCount($this->resource);
    }
}

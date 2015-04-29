<?php
namespace Omeka\Api\Representation;

class VocabularyRepresentation extends AbstractEntityRepresentation
{
    /**
     * @var array Cache of property representation members
     */
    protected $properties;

    /**
     * @var array Cache of resource class representation members
     */
    protected $resourceClasses;

    /**
     * {@inheritDoc}
     */
    public function getControllerName()
    {
        return 'vocabulary';
    }

    /**
     * {@inheritDoc}
     */
    public function getJsonLd()
    {
        return array(
            'o:namespace_uri' => $this->namespaceUri(),
            'o:prefix'        => $this->prefix(),
            'o:label'         => $this->label(),
            'o:comment'       => $this->comment(),
            'o:owner'         => $this->getReference(
                null,
                $this->getData()->getOwner(),
                $this->getAdapter('users')
            ),
        );
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
        return in_array($this->prefix(), array('dcterms', 'dctype'));
    }

    /**
     * Return the vocabulary prefix.
     *
     * @return string
     */
    public function prefix()
    {
        return $this->getData()->getPrefix();
    }

    /**
     * Return the vocabulary namespace URI.
     *
     * @return string
     */
    public function namespaceUri()
    {
        return $this->getData()->getNamespaceUri();
    }

    /**
     * Return the vocabulary label.
     *
     * @return string
     */
    public function label()
    {
        return $this->getData()->getLabel();
    }
    
    /**
     * Return the vocabulary comment.
     *
     * @return string
     */
    public function comment()
    {
        return $this->getData()->getComment();
    }

    /**
     * Return property members.
     *
     * @return array
     */
    public function properties()
    {
        if (isset($this->properties)) {
            return $this->properties;
        }
        $this->properties = array();
        $propertyAdapter = $this->getAdapter('properties');
        foreach ($this->getData()->getProperties() as $propertyEntity) {
            $this->properties[] = $propertyAdapter
                ->getRepresentation(null, $propertyEntity);
        }
        return $this->properties;
    }

    /**
     * Return resource class members.
     *
     * @return array
     */
     public function resourceClasses()
     {
        if (isset($this->resourceClasses)) {
            return $this->resourceClasses;
        }
        $this->resourceClasses = array();
        $resourceClassAdapter = $this->getAdapter('resource_classes');
        foreach ($this->getData()->getResourceClasses() as $resourceClass) {
            $this->resourceClasses[] = $resourceClassAdapter
                ->getRepresentation(null, $resourceClass);
        }
        return $this->resourceClasses;
     }

    /**
     * Get this vocabulary's property count.
     *
     * @return int
     */
    public function propertyCount()
    {
        return count($this->getData()->getProperties());
    }

    /**
     * Get this vocabulary's resource class count.
     *
     * @return int
     */
    public function resourceClassCount()
    {
        return count($this->getData()->getResourceClasses());
    }
}

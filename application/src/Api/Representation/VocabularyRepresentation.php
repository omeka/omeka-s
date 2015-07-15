<?php
namespace Omeka\Api\Representation;

class VocabularyRepresentation extends AbstractEntityRepresentation
{
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
        $owner = null;
        if ($this->owner()) {
            $owner = $this->owner()->reference();
        }
        return array(
            'o:namespace_uri' => $this->namespaceUri(),
            'o:prefix' => $this->prefix(),
            'o:label' => $this->label(),
            'o:comment' => $this->comment(),
            'o:owner' => $owner,
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

    public function owner()
    {
        return $this->getAdapter('users')
            ->getRepresentation(null, $this->getData()->getOwner());
    }

    /**
     * Return property members.
     *
     * @return array
     */
    public function properties()
    {
        $properties = array();
        $propertyAdapter = $this->getAdapter('properties');
        foreach ($this->getData()->getProperties() as $propertyEntity) {
            $properties[] = $propertyAdapter->getRepresentation(null, $propertyEntity);
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
        $resourceClasses = array();
        $resourceClassAdapter = $this->getAdapter('resource_classes');
        foreach ($this->getData()->getResourceClasses() as $resourceClass) {
            $resourceClasses[] = $resourceClassAdapter->getRepresentation(null, $resourceClass);
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

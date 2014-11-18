<?php
namespace Omeka\Api\Representation\Entity;

class VocabularyRepresentation extends AbstractEntityRepresentation
{
    /**
     * @var array Cache of property representation members
     */
    protected $properties = array();

    /**
     * @var array Cache of resource class representation members
     */
    protected $resourceClasses = array();

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
     * The custom vocabulary, Dublin Core, and Dublin Core Type vocabularies are
     * integral parts of the software and should not be deleted.
     *
     * @return bool
     */
    public function isPermanent()
    {
        return in_array($this->prefix(), array('omeka', 'dcterms', 'dctype'));
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
        if ('omeka' == $this->prefix()) {
            // If this is the custom vocabulary, dynamically mint the namespace
            // for this Omeka instance.
            $url = $this->getViewHelper('url');
            return $url(
                'custom_vocabulary',
                array(),
                array('force_canonical' => true)
            ) . '#';
        }
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
        if (empty($this->properties)) {
            $this->properties = array();
            $propertyAdapter = $this->getAdapter('properties');
            foreach ($this->getData()->getProperties() as $propertyEntity) {
                $this->properties[] = $propertyAdapter
                    ->getRepresentation(null, $propertyEntity);
            }
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
        if (empty($this->resourceClasses)) {
            $this->resourceClasses = array();
            $resourceClassAdapter = $this->getAdapter('resource_classes');
            foreach ($this->getData()->getResourceClasses() as $resourceClass) {
                $this->resourceClasses[] = $resourceClassAdapter
                    ->getRepresentation(null, $resourceClass);
            }
        }
        return $this->resourceClasses;
     }
}

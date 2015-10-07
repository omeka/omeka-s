<?php
namespace Omeka\Api\Representation;

/**
 * Provide shared functionality for resource classes and properties.
 */
abstract class AbstractVocabularyMemberRepresentation extends AbstractEntityRepresentation
{
    /**
     * {@inheritDoc}
     */
    public function getJsonLd()
    {
        return [
            'o:local_name' => $this->localName(),
            'o:label' => $this->label(),
            'o:comment' => $this->comment(),
            'o:term' => $this->term(),
            'o:vocabulary' => $this->vocabulary()->getReference(),
        ];
    }

    /**
     * Return this member's vocabulary representation.
     *
     * @return VocabularyRepresentation
     */
    public function vocabulary()
    {
        return $this->getAdapter('vocabularies')
            ->getRepresentation(null, $this->getData()->getVocabulary());
    }

    /**
     * Return this member's local name.
     *
     * @return string
     */
    public function localName()
    {
        return $this->getData()->getLocalName();
    }

    /**
     * Return this member's label.
     *
     * @return string
     */
    public function label()
    {
        return $this->getData()->getLabel();
    }

    /**
     * Return this member's comment.
     *
     * @return string
     */
    public function comment()
    {
        return $this->getData()->getComment();
    }

    /**
     * Return this member's term (QName).
     *
     * @return string
     */
    public function term()
    {
        return $this->vocabulary()->prefix() . ':' . $this->localName();
    }

    /**
     * Return this member's full URI
     *
     * @return string
     */
    public function uri()
    {
        return $this->vocabulary()->namespaceUri() . $this->localName();
    }
}

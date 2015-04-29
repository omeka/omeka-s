<?php
namespace Omeka\Api\Representation;

/**
 * Provide shared functionality for resource classes and properties.
 */
abstract class AbstractVocabularyMemberRepresentation extends AbstractEntityRepresentation
{
    /**
     * @var VocabularyRepresentation Cache of this member's vocabulary
     */
    protected $vocabulary;

    /**
     * {@inheritDoc}
     */
    public function getJsonLd()
    {
        return array(
            'o:local_name' => $this->localName(),
            'o:label'      => $this->label(),
            'o:comment'    => $this->comment(),
            'o:term'       => $this->term(),
            'o:vocabulary' => $this->getReference(
                null,
                $this->getData()->getVocabulary(),
                $this->getAdapter('vocabularies')
            ),
            'o:owner' => $this->getReference(
                null,
                $this->getData()->getOwner(),
                $this->getAdapter('users')
            ),
        );
    }

    /**
     * Return this member's vocabulary representation.
     *
     * @return VocabularyRepresentation
     */
    public function vocabulary()
    {
        if (!$this->vocabulary) {
            $this->vocabulary = $this->getAdapter('vocabularies')
                ->getRepresentation(null, $this->getData()->getVocabulary());
        }
        return $this->vocabulary;
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

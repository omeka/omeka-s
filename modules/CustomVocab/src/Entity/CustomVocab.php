<?php
namespace CustomVocab\Entity;

use Omeka\Entity\AbstractEntity;
use Omeka\Entity\ItemSet;
use Omeka\Entity\User;

/**
 * @Entity
 */
class CustomVocab extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @Column(unique=true, length=190)
     */
    protected $label;

    /**
     * @Column(nullable=true, length=190)
     */
    protected $lang;

    /**
     * @ManyToOne(targetEntity="Omeka\Entity\ItemSet")
     * @JoinColumn(onDelete="SET NULL")
     */
    protected $itemSet;

    /**
     * @Column(nullable=true, type="json")
     */
    protected $terms;

    /**
     * @Column(nullable=true, type="json")
     */
    protected $uris;

    /**
     * @ManyToOne(targetEntity="Omeka\Entity\User")
     * @JoinColumn(onDelete="SET NULL")
     */
    protected $owner;

    public function getId()
    {
        return $this->id;
    }

    public function setLabel($label)
    {
        $this->label = $label;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function setLang($lang)
    {
        $this->lang = $lang;
    }

    public function getLang()
    {
        return $this->lang;
    }

    public function setItemSet(ItemSet $itemSet = null)
    {
        $this->itemSet = $itemSet;
    }

    public function getItemSet()
    {
        return $this->itemSet;
    }

    public function setTerms($terms)
    {
        $this->terms = $terms;
    }

    public function getTerms()
    {
        return $this->terms;
    }

    public function setUris($uris)
    {
        $this->uris = $uris;
    }

    public function getUris()
    {
        return $this->uris;
    }

    public function setOwner(User $owner = null)
    {
        $this->owner = $owner;
    }

    public function getOwner()
    {
        return $this->owner;
    }
}

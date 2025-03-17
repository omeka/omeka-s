<?php
namespace Collecting\Entity;

use Omeka\Entity\AbstractEntity;

/**
 * @Entity
 */
class CollectingInput extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @ManyToOne(
     *     targetEntity="CollectingPrompt",
     *     inversedBy="inputs"
     * )
     * @JoinColumn(
     *     nullable=false,
     *     onDelete="CASCADE"
     * )
     */
    protected $prompt;

    /**
     * @ManyToOne(
     *     targetEntity="CollectingItem",
     *     inversedBy="inputs"
     * )
     * @JoinColumn(
     *     nullable=false,
     *     onDelete="CASCADE"
     * )
     */
    protected $collectingItem;

    /**
     * @Column(type="text")
     */
    protected $text;

    public function getId()
    {
        return $this->id;
    }

    public function setPrompt(CollectingPrompt $prompt)
    {
        $this->prompt = $prompt;
    }

    public function getPrompt()
    {
        return $this->prompt;
    }

    public function setCollectingItem(CollectingItem $collectingItem)
    {
        $this->collectingItem = $collectingItem;
    }

    public function getCollectingItem()
    {
        return $this->collectingItem;
    }

    public function setText($text)
    {
        $this->text = $text;
    }

    public function getText()
    {
        return $this->text;
    }
}

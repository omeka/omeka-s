<?php
namespace Collecting\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Omeka\Entity\AbstractEntity;
use Omeka\Entity\Property;

/**
 * @Entity
 */
class CollectingPrompt extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @ManyToOne(
     *     targetEntity="CollectingForm",
     *     inversedBy="prompts"
     * )
     * @JoinColumn(
     *     nullable=false,
     *     onDelete="CASCADE"
     * )
     */
    protected $form;

    /**
     * @OneToMany(
     *     targetEntity="CollectingInput",
     *     mappedBy="prompt",
     *     orphanRemoval=true,
     *     cascade={"all"}
     * )
     */
    protected $inputs;

    /**
     * @Column(type="integer")
     */
    protected $position;

    /**
     * @Column
     */
    protected $type;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $text;

    /**
     * @Column(nullable=true)
     */
    protected $inputType;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $selectOptions;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $resourceQuery;

    /**
     * @Column(type="integer", nullable=true)
     */
    protected $customVocab;

    /**
     * @Column(nullable=true)
     */
    protected $mediaType;

    /**
     * @Column(type="boolean", nullable=false)
     */
    protected $required = false;

    /**
     * @ManyToOne(
     *     targetEntity="Omeka\Entity\Property"
     * )
     * @JoinColumn(
     *     nullable=true,
     *     onDelete="CASCADE"
     * )
     */
    protected $property;

    public static function getTypes()
    {
        return [
            'property' => 'Item Property', // @translate
            'media' => 'Item Media', // @translate
            'input' => 'Item Supplementary', // @translate
            'user_name' => 'User Name', // @translate
            'user_email' => 'User Email', // @translate
            'user_private' => 'User Private', // @translate
            'user_public' => 'User Public', // @translate
            'html' => 'HTML', // @translate
        ];
    }

    public static function getInputTypes()
    {
        return [
            'text' => 'Text box (one line)', // @translate
            'textarea' => 'Text box (multiple line)', // @translate
            'select' => 'Select menu', // @translate
            'item' => 'Item resource', // @translate
            'url' => 'URL', // @translate
            'custom_vocab' => 'Custom vocab', // @translate
            'numeric:timestamp' => 'Numeric timestamp', // @translate
            'numeric:interval' => 'Numeric interval', // @translate
            'numeric:duration' => 'Numeric duration', // @translate
            'numeric:integer' => 'Numeric integer', // @translate
        ];
    }

    public function __construct()
    {
        $this->inputs = new ArrayCollection;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setForm(CollectingForm $form)
    {
        $this->form = $form;
    }

    public function getForm()
    {
        return $this->form;
    }

    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setText(?string $text)
    {
        $this->text = is_string($text) ? trim($text) : null;
    }

    public function getText()
    {
        return $this->text;
    }

    public function setInputType($inputType)
    {
        $this->inputType = is_string($inputType) ? trim($inputType) : null;
    }

    public function getInputType()
    {
        return $this->inputType;
    }

    public function setSelectOptions($selectOptions)
    {
        $this->selectOptions = is_string($selectOptions) ? trim($selectOptions) : null;
    }

    public function getSelectOptions()
    {
        return $this->selectOptions;
    }

    public function setResourceQuery($resourceQuery)
    {
        $this->resourceQuery = is_string($resourceQuery) ? trim($resourceQuery) : null;
    }

    public function getResourceQuery()
    {
        return $this->resourceQuery;
    }

    public function setCustomVocab($customVocab)
    {
        // Must be a positive non-zero integer.
        $customVocab = (int) $customVocab;
        $this->customVocab = (0 < $customVocab) ? $customVocab : null;
    }

    public function getCustomVocab()
    {
        return $this->customVocab;
    }

    public function setMediaType($mediaType)
    {
        $this->mediaType = is_string($mediaType) ? trim($mediaType) : null;
    }

    public function getMediaType()
    {
        return $this->mediaType;
    }

    public function setRequired($required)
    {
        $this->required = (bool) $required;
    }

    public function getRequired()
    {
        return $this->required;
    }

    public function setProperty(Property $property = null)
    {
        $this->property = $property;
    }

    public function getProperty()
    {
        return $this->property;
    }
}

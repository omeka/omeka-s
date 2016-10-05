<?php
namespace Omeka\Form\Element;

use Zend\Form\Element\Textarea;

class RestoreTextarea extends Textarea
{
    /**
     * @var array
     */
    protected $attributes = [
        'type' => 'restore_textarea',
    ];

    /**
     * The value to restore in the textarea when the button is clicked.
     *
     * @var string
     */
    protected $restoreValue;

    /**
     * The text of the restore button.
     *
     * @var string
     */
    protected $restoreButtonText = 'Restore'; // @translate

    /**
     * @param array $options
     */
    public function setOptions($options)
    {
        parent::setOptions($options);
        if (isset($this->options['restore_value'])) {
            $this->setRestoreValue($this->options['restore_value']);
        }
        if (isset($this->options['restore_button_text'])) {
            $this->setRestoreButtonText($this->options['restore_button_text']);
        }
        return $this;
    }

    /**
     * Set the restore value.
     *
     * @param string $restoreValue
     */
    public function setRestoreValue($restoreValue)
    {
        $this->restoreValue = $restoreValue;
        return $this;
    }

    /**
     * Get the restore value.
     *
     * @return string
     */
    public function getRestoreValue()
    {
        return $this->restoreValue;
    }

    /**
     * Set the restore button text.
     *
     * @param string $restoreButtonText
     */
    public function setRestoreButtonText($restoreButtonText)
    {
        $this->restoreButtonText = $restoreButtonText;
        return $this;
    }

    /**
     * Set the restore button text.
     *
     * @return string
     */
    public function getRestoreButtonText()
    {
        return $this->restoreButtonText;
    }
}

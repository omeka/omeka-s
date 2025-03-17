<?php
namespace Collecting\Form\Element;

use Laminas\Form\Element;

/**
 * A form element used to add markup to the form.
 */
class PromptHtml extends Element
{
    /**
     * @var string
     */
    protected $html;

    protected $attributes = [
        'type' => 'promptHtml',
    ];

    /**
     * Set the markup.
     *
     * @param string $html
     */
    public function setHtml($html)
    {
        $this->html = $html;
    }

    /**
     * Get the markup.
     *
     * @return string
     */
    public function getHtml()
    {
        return $this->html;
    }
}

<?php
namespace Omeka\View\Helper;

use Zend\View\Helper\AbstractHtmlElement;

class HtmlElement extends AbstractHtmlElement
{
    /**
     * @var array Cached elements and their attributes
     */
    protected $elements = array();

    /**
     * @var string The current element
     */
    protected $element;

    /**
     * HTML element helper
     *
     * @param string $element The current element
     * @return HtmlElement
     */
    public function __invoke($element)
    {
        $this->element = $element;
        if (!isset($this->elements[$element])) {
            $this->elements[$element] = array();
        }
        return $this;
    }

    /**
     * Render the element and its attributes
     *
     * @return string
     */
    public function __toString()
    {
        $attributes = $this->elements[$this->element];
        return '<' . $this->element . $this->htmlAttribs($attributes) . '>';
    }

    /**
     * Set an attribute to the current element
     *
     * @param string $key
     * @param string $value
     */
    public function setAttribute($key, $value)
    {
        $this->elements[$this->element][$key] = $value;
        return $this;
    }

    /**
     * Set attributes to the current element
     *
     * @param array $attributes
     */
    public function setAttributes(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }
        return $this;
    }

    /**
     * Remove an attribute from the current element
     *
     * @param string $key
     */
    public function removeAttribute($key)
    {
        unset($this->elements[$this->element][$key]);
        return $this;
    }

    /**
     * Remove all attributes from the current element
     */
    public function removeAttributes()
    {
        if (isset($this->elements[$this->element])) {
            $this->elements[$this->element] = array();
        }
        return $this;
    }
}

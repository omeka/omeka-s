<?php
namespace Omeka\View\Helper;

use Zend\View\Helper\AbstractHtmlElement;

/**
 * View helper for rendering a HTML hyperlink.
 */
class Hyperlink extends AbstractHtmlElement
{
    /**
     * Render a HTML hyperlink.
     *
     * @param string|null $text The hyperlink text
     * @param string|null $href The hyperlink href URL
     * @param array $attributes The hyperlink attributes
     * @return string
     */
    public function __invoke($text = null, $href = null, array $attributes = [])
    {
        return $this->raw($this->getView()->escapeHtml($text), $href, $attributes);
    }

    /**
     * Render a HTML hyperlink without escaping the content.
     *
     * @param string|null $html The hyperlink content
     * @param string|null $href The hyperlink href URL
     * @param array $attributes The hyperlink attributes
     * @return string
     */
    public function raw($html = null, $href = null, array $attributes = [])
    {
        $attributes['href'] = $href;
        if (($html === null || $html === '') && isset($attributes['title']) && !isset($attributes['aria-label'])) {
            $attributes['aria-label'] = $attributes['title'];
        }
        return '<a' . $this->htmlAttribs($attributes) . '>' . $html . '</a>';
    }
}

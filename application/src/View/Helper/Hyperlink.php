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
        $attributes['href'] = $href;
        return '<a' . $this->htmlAttribs($attributes) . '>' . $this->getView()->escapeHtml($text) . '</a>';
    }
}

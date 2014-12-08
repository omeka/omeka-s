<?php
namespace Omeka\View\Helper;

use Zend\View\Helper\AbstractHtmlElement;

class Hyperlink extends AbstractHtmlElement
{
    /**
     * Render a HTML hyperlink.
     *
     * @param string $text The hyperlink text
     * @param string|null $href The hyperlink href URL
     * @param array $attributes The hyperlink attributes
     * @return string
     */
    public function __invoke($text, $href, array $attributes = array())
    {
        $escape = $this->getView()->plugin('escapehtml');

        if (null === $href) {
            return $escape($text);
        }

        $attributes['href'] = $href;
        return '<a' . $this->htmlAttribs($attributes) . '>' . $escape($text) . '</a>';
    }
}

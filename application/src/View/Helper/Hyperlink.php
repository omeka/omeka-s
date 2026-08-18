<?php
namespace Omeka\View\Helper;

use Laminas\View\Helper\AbstractHtmlElement;

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
        $view = $this->getView();
        $externalLinkTag = '';
        $attributes['href'] = $href;
        if (($html === null || $html === '') && isset($attributes['title']) && !isset($attributes['aria-label'])) {
            $attributes['aria-label'] = $attributes['title'];
        }
        if (isset($attributes['target']) && ($attributes['target'] == '_blank')) {
            $translate = $view->plugin('translate');
            $externalLinkTag = '<span class="sr-only">' . $translate('(external link)') . '</span>';
        }
        return '<a' . $this->htmlAttribs($attributes) . '>' . $html . $externalLinkTag . '</a>';
    }
}

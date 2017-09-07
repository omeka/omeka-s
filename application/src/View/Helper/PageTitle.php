<?php
namespace Omeka\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\View\Helper\Placeholder\Container\AbstractContainer;

/**
 * View helper for rendering a title heading for a page.
 */
class PageTitle extends AbstractHelper
{
    /**
     * Render a title heading for a page.
     *
     * The passed title is added to the title element to the head as well as
     * returned inside an h1 for printing on the page.
     *
     * @param string $title
     * @param int $level "h" level of the heading tag to print. A level of zero
     *  or a negative value will omit the heading tag.
     * @return string
     */
    public function __invoke($title, $level = 1, $subheadLabel = null, $actionLabel = null)
    {
        $view = $this->getView();
        $view->headTitle($subheadLabel, AbstractContainer::PREPEND);
        $view->headTitle($title, AbstractContainer::PREPEND);
        $view->headTitle($actionLabel, AbstractContainer::PREPEND);
        $subhead = '';
        $action = '';
        if ($subheadLabel) {
            $subhead = '<span class="subhead">' . $view->escapeHtml($subheadLabel) . '</span>';
        }
        if ($actionLabel) {
            $action = '<span class="action">' . $view->escapeHtml($actionLabel) . '</span>';
        }

        $level = (int) $level;
        if ($level > 0) {
            return "<h$level>" . $subhead . '<span class="title">' . $view->escapeHtml($title) . '</span>' . $action . "</h$level>";
        }

        return $view->escapeHtml($title);
    }
}

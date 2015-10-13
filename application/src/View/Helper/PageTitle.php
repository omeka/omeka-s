<?php
namespace Omeka\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\View\Helper\Placeholder\Container\AbstractContainer;

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
    public function __invoke($title, $level = 1)
    {
        $view = $this->getView();
        $view->headTitle($title, AbstractContainer::PREPEND);

        $level = (int) $level;
        if ($level > 0) {
            return "<h$level>" . $view->escapeHtml($title) . "</h$level>";
        }

        return $view->escapeHtml($title);
    }
}

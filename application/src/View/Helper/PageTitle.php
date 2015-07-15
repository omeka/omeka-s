<?php
namespace Omeka\View\Helper;

use Zend\View\Helper\AbstractHelper;

class PageTitle extends AbstractHelper
{
    /**
     * Render a title heading for a page.
     *
     * The passed title is added to the title element to the head as well as
     * returned inside an h1 for printing on the page.
     *
     * @param string $title
     * @return string
     */
    public function __invoke($title)
    {
        $view = $this->getView();
        $view->headTitle($title);
        return '<h1>' . $view->escapeHtml($title) . '</h1>';
    }
}

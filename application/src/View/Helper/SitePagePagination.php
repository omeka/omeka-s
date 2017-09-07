<?php
namespace Omeka\View\Helper;

use Omeka\Api\Representation\SitePageRepresentation;
use Zend\View\Exception;
use Zend\View\Helper\AbstractHelper;

/**
 * View helper for rendering site page pagination.
 */
class SitePagePagination extends AbstractHelper
{
    /**
     * @var SitePageRepresentation
     */
    protected $page;

    /**
     * @var null|SitePageRepresentation
     */
    protected $prevPage;

    /**
     * @var null|SitePageRepresentation
     */
    protected $nextPage;

    /**
     * @var bool Whether the page is found in site navigation.
     */
    protected $pageInNav = true;

    /**
     * Return the site page pagination markup.
     *
     * Returns null if the page is not found in site navigation.
     *
     * @throws Exception\RuntimeException
     * @return string|null
     */
    public function __invoke()
    {
        if (null === $this->page) {
            throw new Exception\RuntimeException('No site page provided');
        }
        if (!$this->pageInNav) {
            return null;
        }
        return $this->getView()->partial(
            'common/site-page-pagination',
            [
                'page' => $this->page,
                'prevPage' => $this->prevPage,
                'nextPage' => $this->nextPage,
            ]
        );
    }

    /**
     * Set the current, previous, and next page.
     *
     * @param SitePageRepresentation $page
     */
    public function setPage(SitePageRepresentation $page)
    {
        $this->page = $page;
        $linkedPages = $page->site()->linkedPages();
        if (!array_key_exists($page->id(), $linkedPages)) {
            // Page not found in navigation. Don't attempt to find prev/next.
            $this->pageInNav = false;
            return;
        }
        // Iterate the linked pages, setting the previous and next pages, if any.
        while ($linkedPage = current($linkedPages)) {
            if ($page->id() === $linkedPage->id()) {
                $this->nextPage = next($linkedPages);
                break;
            }
            $this->prevPage = $linkedPage;
            next($linkedPages);
        }
    }
}

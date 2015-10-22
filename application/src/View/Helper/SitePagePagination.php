<?php
namespace Omeka\View\Helper;

use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Exception\NotFoundException;
use Zend\View\Exception;
use Zend\View\Helper\AbstractHelper;

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
     * Set the current page.
     *
     * Automatically sets the prev/next pages if any.
     *
     * @throws Exception\InvalidArgumentException
     * @param SitePageRepresentation $page
     */
    public function setPage(SitePageRepresentation $page)
    {
        $this->page = $page;

        // Build an array containing all page IDs in navigation in the order
        // they appear.
        $pageIds = [];
        $iterate = function ($linksIn) use (&$iterate, &$pageIds, $page)
        {
            foreach ($linksIn as $key => $data) {
                if ('page' === $data['type']) {
                    $pageIds[] = $data['data']['id'];
                }
                if (isset($data['links'])) {
                    $iterate($data['links']);
                }
            }
        };
        $iterate($page->site()->navigation());

        $key = array_search($page->id(), $pageIds);
        if (false === $key) {
            // Page not found in navigation. Don't attempt to find prev/next.
            $this->pageInNav = false;
            return;
        }

        $api = $this->getView()->api();
        $prevPage = null;
        if (isset($pageIds[$key - 1])) {
            try {
                $prevPage = $api->read('site_pages', $pageIds[$key - 1])->getContent();
            } catch (NotFoundException $e) {}
        }
        $nextPage = null;
        if (isset($pageIds[$key + 1])) {
            try {
                $nextPage = $api->read('site_pages', $pageIds[$key + 1])->getContent();
            } catch (NotFoundException $e) {}
        }

        $this->prevPage = $prevPage;
        $this->nextPage = $nextPage;
    }
}

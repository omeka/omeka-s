<?php
namespace Omeka\View\Helper;

use Omeka\Api\Representation\SitePageRepresentation;
use Zend\View\Helper\AbstractHelper;

class SitePagePagination extends AbstractHelper
{
    public function __invoke(SitePageRepresentation $page)
    {
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
            // This page is not in navigation.
            return null;
        }
        $api = $this->getView()->api();
        $prevPage = null;
        if (isset($pageIds[$key - 1])) {
            $prevPage = $api->read('site_pages', $pageIds[$key - 1])->getContent();
        }
        $nextPage = null;
        if (isset($pageIds[$key + 1])) {
            $nextPage = $api->read('site_pages', $pageIds[$key + 1])->getContent();
        }

        return $this->getView()->partial(
            'common/site-page-pagination',
            [
                'currentPage' => $page,
                'prevPage' => $prevPage,
                'nextPage' => $nextPage,
            ]
        );
    }
}

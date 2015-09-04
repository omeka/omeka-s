<?php
namespace Omeka\Theme;

use Omeka\Entity\Site;
use Omeka\Api\Representation\SiteRepresentation;

class NavigationTranslator
{
    /**
     * Translate site navigation to Zend navigation.
     *
     * @param Site $site
     * @return array
     */
    public function toZend(Site $site)
    {
        $buildPages = function ($pagesIn) use (&$buildPages, $site)
        {
            $pagesOut = array();
            foreach ($pagesIn as $key => $page) {
                if (!isset($page['type'])) {
                    continue;
                }
                switch ($page['type']) {
                    case 'browse':
                        $pagesOut[$key] = array(
                            'label' => 'Browse',
                            'route' => 'site/browse',
                            'params' => array(
                                'site-slug' => $site->getSlug(),
                            ),
                        );
                        break;
                    case 'page':
                        $sitePage = $site->getPages()->get($page['id']);
                        if ($sitePage) {
                            $pagesOut[$key] = array(
                                'label' => $sitePage->getTitle(),
                                'route' => 'site/page',
                                'params' => array(
                                    'site-slug' => $site->getSlug(),
                                    'page-slug' => $sitePage->getSlug(),
                                ),
                            );
                        } else {
                            $pagesOut[$key] = array(
                                'type' => 'uri',
                                'label' => '[invalid page]',
                            );
                        }
                        break;
                    default:
                        continue 2;
                }
                if (isset($page['pages'])) {
                    $pagesOut[$key]['pages'] = $buildPages($page['pages']);
                }
            }
            return $pagesOut;
        };
        $pages = $buildPages($site->getNavigation());
        if (!$pages) {
            // The site must have at least one page for navigation to work.
            $pages = array(array(
                'label' => 'Home',
                'route' => 'site',
                'params' => array(
                    'site-slug' => $site->getSlug(),
                ),
            ));
        }
        return $pages;
    }

    public function toJstreeNavTree(SiteRepresentation $site)
    {
        $sitePages = $site->pages();
        $buildPages = function ($pagesIn) use (&$buildPages, $sitePages) {
            $pagesOut = array();
            foreach ($pagesIn as $key => $page) {
                if (isset($sitePages[$page['id']])) {
                    $sitePage = $sitePages[$page['id']];
                    $pagesOut[$key] = array(
                        'text' => $sitePage->title(),
                        'data' => array(
                            'type' => 'page',
                            'id' => $sitePage->id(),
                        ),
                    );
                    if (isset($page['pages'])) {
                        $pagesOut[$key]['children'] = $buildPages($page['pages']);
                    }
                }
            }
            return $pagesOut;
        };
        return $buildPages($site->navigation());
    }

    public function toJstreePageTree(SiteRepresentation $site)
    {
        $sitePages = $site->pages();
        $navPages = array();
        $buildPages = function ($pagesIn) use (&$buildPages, &$navPages, $sitePages) {
            $pagesOut = array();
            foreach ($pagesIn as $key => $page) {
                if (isset($sitePages[$page['id']])) {
                    $navPages[] = $page['id'];
                    if (isset($page['pages'])) {
                        $buildPages($page['pages']);
                    }
                }
            }
        };
        $buildPages($site->navigation());
        $pageTree = array();
        foreach ($sitePages as $id => $page) {
            if (!in_array($id, $navPages)) {
                $pageTree[] = array(
                    'text' => $page->title(),
                    'data' => array(
                        'type' => 'page',
                        'id' => $page->id(),
                    ),
                );
            }
        }
        return $pageTree;
    }

    public function fromJstree(array $jstree)
    {
        $buildPages = function ($pagesIn) use (&$buildPages) {
            $pagesOut = array();
            foreach ($pagesIn as $key => $page) {
                $pagesOut[$key] = $page['data'];
                if ($page['children']) {
                    $pagesOut[$key]['pages'] = $buildPages($page['children']);
                }
            }
            return $pagesOut;
        };
        return $buildPages($jstree);
    }
}

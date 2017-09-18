<?php
namespace Omeka\Api\Representation;

use RecursiveIteratorIterator;
use Zend\Navigation\Service\ConstructedNavigationFactory;

class SiteRepresentation extends AbstractEntityRepresentation
{
    /**
     * @var \Zend\Navigation\Navigation
     */
    protected $publicNavContainer;

    /**
     * {@inheritDoc}
     */
    public function getJsonLdType()
    {
        return 'o:Site';
    }

    /**
     * {@inheritDoc}
     */
    public function adminUrl($action = null, $canonical = false)
    {
        $url = $this->getViewHelper('Url');
        return $url(
            'admin/site/slug/action',
            [
                'site-slug' => $this->slug(),
                'action' => $action,
            ],
            ['force_canonical' => $canonical]
        );
    }
    public function getJsonLd()
    {
        $pages = [];
        foreach ($this->pages() as $pageRepresentation) {
            $pages[] = $pageRepresentation->getReference();
        }

        $owner = null;
        if ($this->owner()) {
            $owner = $this->owner()->getReference();
        }

        $created = [
            '@value' => $this->getDateTime($this->created()),
            '@type' => 'http://www.w3.org/2001/XMLSchema#dateTime',
        ];
        $modified = null;
        if ($this->modified()) {
            $modified = [
               '@value' => $this->getDateTime($this->modified()),
               '@type' => 'http://www.w3.org/2001/XMLSchema#dateTime',
            ];
        }

        return [
            'o:slug' => $this->slug(),
            'o:theme' => $this->theme(),
            'o:title' => $this->title(),
            'o:navigation' => $this->navigation(),
            'o:item_pool' => $this->itemPool(),
            'o:owner' => $owner,
            'o:created' => $created,
            'o:modified' => $modified,
            'o:is_public' => $this->isPublic(),
            'o:page' => $pages,
            'o:site_permission' => $this->sitePermissions(),
            'o:site_item_set' => $this->siteItemSets(),
        ];
    }

    public function slug()
    {
        return $this->resource->getSlug();
    }

    public function title()
    {
        return $this->resource->getTitle();
    }

    public function theme()
    {
        return $this->resource->getTheme();
    }

    public function navigation()
    {
        return $this->resource->getNavigation();
    }

    public function itemPool()
    {
        return $this->resource->getItemPool();
    }

    public function created()
    {
        return $this->resource->getCreated();
    }

    public function modified()
    {
        return $this->resource->getModified();
    }

    public function isPublic()
    {
        return $this->resource->isPublic();
    }

    public function pages()
    {
        $pages = [];
        $pageAdapter = $this->getAdapter('site_pages');
        foreach ($this->resource->getPages() as $page) {
            $pages[$page->getId()] = $pageAdapter->getRepresentation($page);
        }
        return $pages;
    }

    /**
     * Return pages that are linked in site navigation, in the order they appear.
     *
     * @return array An array of page representations
     */
    public function linkedPages()
    {
        $linkedPages = [];
        $pages = $this->pages();
        $iterate = function ($linksIn) use (&$iterate, &$linkedPages, $pages) {
            foreach ($linksIn as $key => $data) {
                if ('page' === $data['type'] && isset($pages[$data['data']['id']])) {
                    $linkedPages[$data['data']['id']] = $pages[$data['data']['id']];
                }
                if (isset($data['links'])) {
                    $iterate($data['links']);
                }
            }
        };
        $iterate($this->navigation());
        return $linkedPages;
    }

    /**
     * Return pages that are not linked in site navigation.
     *
     * @return array An array of page represenatations
     */
    public function notLinkedPages()
    {
        return array_diff_key($this->pages(), $this->linkedPages());
    }

    /**
     * Return the permissions assigned to this site.
     *
     * @return array
     */
    public function sitePermissions()
    {
        $sitePermissions = [];
        foreach ($this->resource->getSitePermissions() as $sitePermission) {
            $sitePermissions[] = new SitePermissionRepresentation(
                $sitePermission, $this->getServiceLocator());
        }
        return $sitePermissions;
    }

    /**
     * Return the item sets assigned to this site.
     *
     * @return array
     */
    public function siteItemSets()
    {
        $itemSets = [];
        foreach ($this->resource->getSiteItemSets() as $itemSet) {
            $itemSets[] = new SiteItemSetRepresentation($itemSet, $this->getServiceLocator());
        }
        return $itemSets;
    }

    /**
     * Get the owner representation of this resource.
     *
     * @return UserRepresentation
     */
    public function owner()
    {
        return $this->getAdapter('users')
            ->getRepresentation($this->resource->getOwner());
    }

    public function siteUrl($siteSlug = null, $canonical = false)
    {
        if (!$siteSlug) {
            $siteSlug = $this->slug();
        }
        $url = $this->getViewHelper('Url');
        return $url(
            'site',
            ['site-slug' => $siteSlug],
            ['force_canonical' => $canonical]
        );
    }

    /**
     * Get the navigation helper for admin-side nav for this site for the current user
     *
     * @return \Zend\View\Helper\Navigation
     */
    public function adminNav()
    {
        $navHelper = $this->getViewHelper('Navigation');
        $nav = $navHelper('Zend\Navigation\Site');

        $iterator = new RecursiveIteratorIterator($nav->getContainer(), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $page) {
            if ($page->getPrivilege() && ! $page->getResource()) {
                $page->setResource($this->resource);
            }
        }

        return $nav;
    }

    /**
     * Get the navigation helper for public-side nav for this site
     *
     * @return \Zend\View\Helper\Navigation
     */
    public function publicNav()
    {
        // Build a new Navigation helper so these changes don't leak around to other places,
        // then set it to always disable translation for any of its "child" helpers (menu,
        // breadcrumb, etc.)
        $helper = $this->getServiceLocator()->get('ViewHelperManager')->build('Navigation');
        $helper->getPluginManager()->addInitializer(function ($container, $plugin) {
            $plugin->setTranslatorEnabled(false);
        });
        $nav = $helper($this->getPublicNavContainer());
        return $nav;
    }

    /**
     * Get the navigation container for this site's public nav
     *
     * @return \Zend\Navigation\Navigation
     */
    protected function getPublicNavContainer()
    {
        if (!$this->publicNavContainer) {
            $services = $this->getServiceLocator();
            $navTranslator = $services->get('Omeka\Site\NavigationTranslator');
            $factory = new ConstructedNavigationFactory($navTranslator->toZend($this));
            $this->publicNavContainer = $factory($services, '');
        }

        return $this->publicNavContainer;
    }
}

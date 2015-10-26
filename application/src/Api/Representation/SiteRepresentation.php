<?php
namespace Omeka\Api\Representation;

class SiteRepresentation extends AbstractEntityRepresentation
{
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
            'admin/site/default',
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
        $iterate = function ($linksIn) use (&$iterate, &$linkedPages, $pages)
        {
            foreach ($linksIn as $key => $data) {
                if ('page' === $data['type']) {
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
            $sitePermissions[]= new SitePermissionRepresentation(
                $sitePermission, $this->getServiceLocator());
        }
        return $sitePermissions;
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
}

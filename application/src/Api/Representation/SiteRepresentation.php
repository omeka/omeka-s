<?php
namespace Omeka\Api\Representation;

use Omeka\Api\Representation\SitePermissionRepresentation;

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
            array(
                'site-slug' => $this->slug(),
                'action' => $action,
            ),
            array('force_canonical' => $canonical)
        );
    }
    public function getJsonLd()
    {
        $pages = array();
        foreach ($this->pages() as $pageRepresentation) {
            $pages[] = $pageRepresentation->getReference();
        }

        $owner = null;
        if ($this->owner()) {
            $owner = $this->owner()->getReference();
        }

        $created = array(
            '@value' => $this->getDateTime($this->created()),
            '@type' => 'http://www.w3.org/2001/XMLSchema#dateTime',
        );
        $modified = null;
        if ($this->modified()) {
            $modified = array(
               '@value' => $this->getDateTime($this->modified()),
               '@type' => 'http://www.w3.org/2001/XMLSchema#dateTime',
            );
        }

        return array(
            'o:slug' => $this->slug(),
            'o:theme' => $this->theme(),
            'o:title' => $this->title(),
            'o:navigation' => $this->navigation(),
            'o:owner' => $owner,
            'o:created' => $created,
            'o:modified' => $modified,
            'o:is_public' => $this->isPublic(),
            'o:page' => $pages,
            'o:site_permission' => $this->sitePermissions(),
        );
    }

    public function slug()
    {
        return $this->getData()->getSlug();
    }

    public function title()
    {
        return $this->getData()->getTitle();
    }

    public function theme()
    {
        return $this->getData()->getTheme();
    }

    public function navigation()
    {
        return $this->getData()->getNavigation();
    }

    public function created()
    {
        return $this->getData()->getCreated();
    }

    public function modified()
    {
        return $this->getData()->getModified();
    }

    public function isPublic()
    {
        return $this->getData()->isPublic();
    }

    public function pages()
    {
        $pages = array();
        $pageAdapter = $this->getAdapter('site_pages');
        foreach ($this->getData()->getPages() as $page) {
            $pages[$page->getId()] = $pageAdapter->getRepresentation(null, $page);
        }
        return $pages;
    }

    /**
     * Return the permissions assigned to this site.
     *
     * @return array
     */
    public function sitePermissions()
    {
        $sitePermissions = array();
        foreach ($this->getData()->getSitePermissions() as $sitePermission) {
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
            ->getRepresentation(null, $this->getData()->getOwner());
    }

    public function siteUrl($siteSlug = null, $canonical = false)
    {
        if (!$siteSlug) {
            $siteSlug = $this->slug();
        }
        $url = $this->getViewHelper('Url');
        return $url(
            'site',
            array('site-slug' => $siteSlug),
            array('force_canonical' => $canonical)
        );
    }
}

<?php
namespace Omeka\Api\Representation;

class SitePageRepresentation extends AbstractEntityRepresentation
{
    /**
     * {@inheritDoc}
     */
    public function getJsonLdType()
    {
        return 'o:SitePage';
    }

    public function getJsonLd()
    {
        $entity = $this->resource;
        return [
            'o:slug' => $this->slug(),
            'o:title' => $this->title(),
            'o:block' => $this->blocks(),
            'o:site' => $this->site()->getReference(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function adminUrl($action = null, $canonical = false)
    {
        $url = $this->getViewHelper('Url');
        return $url(
            'admin/site/page/default',
            [
                'site-slug' => $this->site()->slug(),
                'page-slug' => $this->slug(),
                'action' => $action,
            ],
            ['force_canonical' => $canonical]
        );
    }

    public function slug()
    {
        return $this->resource->getSlug();
    }

    public function title()
    {
        return $this->resource->getTitle();
    }

    /**
     * Get the blocks assigned to this page.
     *
     * @return array
     */
    public function blocks()
    {
        $blocks = [];
        foreach ($this->resource->getBlocks() as $block) {
            $blocks[]= new SitePageBlockRepresentation(
                $block, $this->getServiceLocator());
        }
        return $blocks;
    }

    public function site()
    {
        return $this->getAdapter('sites')
            ->getRepresentation($this->resource->getSite());
    }

    public function siteUrl($siteSlug = null, $canonical = false)
    {
        if (!$siteSlug) {
            $siteSlug = $this->site()->slug();
        }
        $url = $this->getViewHelper('Url');
        return $url(
            'site/page',
            [
                'site-slug' => $siteSlug,
                'page-slug' => $this->slug(),
            ],
            ['force_canonical' => $canonical]
        );
    }
}

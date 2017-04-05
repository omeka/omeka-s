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
            'o:title' => $this->title(),
            'o:block' => $this->blocks(),
            'o:site' => $this->site()->getReference(),
            'o:created' => $created,
            'o:modified' => $modified,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function adminUrl($action = null, $canonical = false)
    {
        $url = $this->getViewHelper('Url');
        return $url(
            'admin/site/slug/page/default',
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
            $blocks[] = new SitePageBlockRepresentation(
                $block, $this->getServiceLocator());
        }
        return $blocks;
    }

    public function site()
    {
        return $this->getAdapter('sites')
            ->getRepresentation($this->resource->getSite());
    }

    public function created()
    {
        return $this->resource->getCreated();
    }

    public function modified()
    {
        return $this->resource->getModified();
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

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
        $entity = $this->getData();
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
            'admin/site/page',
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
        return $this->getData()->getSlug();
    }

    public function title()
    {
        return $this->getData()->getTitle();
    }

    /**
     * Get the blocks assigned to this page.
     *
     * @return array
     */
    public function blocks()
    {
        $blocks = [];
        foreach ($this->getData()->getBlocks() as $block) {
            $blocks[]= new SitePageBlockRepresentation(
                $block, $this->getServiceLocator());
        }
        return $blocks;
    }

    public function site()
    {
        return $this->getAdapter('sites')
            ->getRepresentation(null, $this->getData()->getSite());
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

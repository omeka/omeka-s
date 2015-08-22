<?php
namespace Omeka\Api\Representation;

class SitePageRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLd()
    {
        $entity = $this->getData();
        return array(
            'o:slug' => $this->slug(),
            'o:title' => $this->title(),
            'o:block' => $this->blocks(),
            'o:site' => $this->site()->getReference(),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function adminUrl($action = null, $canonical = false)
    {
        $url = $this->getViewHelper('Url');
        return $url(
            'admin/site/page',
            array(
                'site-slug' => $this->site()->slug(),
                'page-slug' => $this->slug(),
                'action' => $action,
            ),
            array('force_canonical' => $canonical)
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
        $blocks = array();
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
}

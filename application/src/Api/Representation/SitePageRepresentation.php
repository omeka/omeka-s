<?php
namespace Omeka\Api\Representation;

class SitePageRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLd()
    {
        $entity = $this->getData();
        return array(
            'o:slug'       => $entity->getSlug(),
            'o:title'      => $entity->getTitle(),
            'o:site'      => $this->getReference(
                null,
                $this->getData()->getSite(),
                $this->getAdapter('sites')
            ),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function url($action = null)
    {
        $url = $this->getViewHelper('Url');
        return $url(
            'admin/site/page',
            array(
                'site-slug' => $this->site()->slug(),
                'page-slug' => $this->slug(),
                'action' => $action,
            )
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

    public function site()
    {
        return $this->getAdapter('sites')
            ->getRepresentation(null, $this->getData()->getSite());
    }
}

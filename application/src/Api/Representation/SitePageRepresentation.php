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

<?php
namespace Omeka\Api\Representation\Entity;

class SiteRepresentation extends AbstractEntityRepresentation
{
    /**
     * {@inheritDoc}
     */
    public function getControllerName()
    {
        return 'site';
    }

    public function getJsonLd()
    {
        $entity = $this->getData();
        return array(
            'o:slug'       => $entity->getSlug(),
            'o:theme'      => $entity->getTheme(),
            'o:title'      => $entity->getTitle(),
            'o:navigation' => $entity->getNavigation(),
            'o:owner'      => $this->getReference(
                null,
                $this->getData()->getOwner(),
                $this->getAdapter('users')
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

    public function theme()
    {
        return $this->getData()->getTheme();
    }

    public function navigation()
    {
        return $this->getData()->getNavigation();
    }
}

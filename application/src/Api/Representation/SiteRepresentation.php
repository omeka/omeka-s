<?php
namespace Omeka\Api\Representation;

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
        $jsonLd = array(
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

        $pageAdapter = $this->getAdapter('site_pages');
        foreach ($entity->getPages() as $page) {
            $jsonLd['o:page'][] = $this->getReference(
                null, $page, $pageAdapter);
        }
        return $jsonLd;
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
}

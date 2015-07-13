<?php
namespace Omeka\Api\Representation;

class SiteRepresentation extends AbstractEntityRepresentation
{
    /**
     * @var array
     */
    protected $pages;

    /**
     * {@inheritDoc}
     */
    public function url($action = null)
    {
        $url = $this->getViewHelper('Url');
        return $url(
            'admin/site/default',
            array(
                'site-slug' => $this->slug(),
                'action' => $action,
            )
        );
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

        $sitePermissions = array();
        foreach ($entity->getSitePermissions() as $sitePermission) {
            $jsonLd['o:site_permission'][] = array(
                'o:user' => $this->getReference(
                    null,
                    $sitePermission->getUser(),
                    $this->getAdapter('users')
                ),
                'o:admin' => $sitePermission->getAdmin(),
                'o:attach' => $sitePermission->getAttach(),
                'o:edit' => $sitePermission->getEdit(),
            );
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

    public function pages()
    {
        if (isset($this->pages)) {
            return $this->pages;
        }
        $this->pages = array();
        $pageAdapter = $this->getAdapter('site_pages');
        foreach ($this->getData()->getPages() as $pageEntity) {
            $this->pages[] = $pageAdapter->getRepresentation(null, $pageEntity);
        }
        return $this->pages;
    }

    /**
     * Return the permissions assigned to this site.
     *
     * Since site permissions are not API resources, this cannot return an array
     * of representations. Instead this returns the array of site permissions
     * as built by {@link self::getJsonLd()}.
     *
     * @return array
     */
    public function sitePermissions()
    {
        $jsonLd = $this->getJsonLd();
        return $jsonLd['o:site_permission'];
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

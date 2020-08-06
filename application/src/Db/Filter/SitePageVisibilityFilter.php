<?php
namespace Omeka\Db\Filter;

use Doctrine\ORM\Mapping\ClassMetaData;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Laminas\ServiceManager\ServiceLocatorInterface;

/**
 * Filter site pages by visibility.
 *
 * Checks to see if the current user has permission to view site page. A page is
 * public if it is public and if the site is public.
 */
class SitePageVisibilityFilter extends SQLFilter
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if ($targetEntity->getName() !== \Omeka\Entity\SitePage::class) {
            return '';
        }

        $identity = $this->serviceLocator->get('Omeka\AuthenticationService')->getIdentity();
        // Visitors can view public pages in public sites.
        if (!$identity) {
            return "$targetTableAlias.id IN (SELECT sp.id FROM site_page sp INNER JOIN site st ON st.id = sp.site_id WHERE sp.id = $targetTableAlias.id AND sp.is_public = 1 AND st.is_public = 1)";
        }

        // Note: site pages have no owner.
        // TODO Authenticated users can see pages according to their role or site permissions.
        return '';
    }

    /**
     * @param ServiceLocatorInterface $serviceLocator
     * @return self
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
        return $this;
    }
}

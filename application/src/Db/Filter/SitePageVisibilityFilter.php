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

        $acl = $this->serviceLocator->get('Omeka\Acl');
        if ($acl->userIsAllowed(\Omeka\Entity\SitePage::class, 'view-all')) {
            return '';
        }

        $identity = $this->serviceLocator->get('Omeka\AuthenticationService')->getIdentity();
        // Visitors can view public pages in public sites.
        if (!$identity) {
            return "$targetTableAlias.id = (SELECT sp.id FROM site_page sp INNER JOIN site st ON st.id = sp.site_id WHERE sp.id = $targetTableAlias.id AND sp.is_public = 1 AND st.is_public = 1)";
        }

        // Authenticated users can see public pages and any page in sites they
        // own or where they have a role. Note: site pages have no owner.
        return sprintf(
            '%1$s.id = (SELECT sp.id FROM site_page sp INNER JOIN site st ON st.id = sp.site_id LEFT JOIN site_permission spm ON spm.site_id = st.id AND spm.user_id = %2$s WHERE sp.id = %1$s.id AND ((sp.is_public = 1 AND st.is_public = 1) OR (st.owner_id = %2$s) OR (spm.user_id IS NOT NULL)))',
            $targetTableAlias,
            (int) $identity->getId()
        );
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

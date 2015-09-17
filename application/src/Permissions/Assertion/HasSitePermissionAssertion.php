<?php
namespace Omeka\Permissions\Assertion;

use Doctrine\Common\Collections\Criteria;
use Omeka\Entity\Site;
use Omeka\Entity\SitePage;
use Omeka\Entity\SitePermission;
use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Assertion\AssertionInterface;
use Zend\Permissions\Acl\Resource\ResourceInterface;
use Zend\Permissions\Acl\Role\RoleInterface;

class HasSitePermissionAssertion implements AssertionInterface
{
    protected $roleNumber;

    public function __construct($role)
    {
        $this->roleNumber = $this->getRoleNumber($role);
    }

    public function assert(Acl $acl, RoleInterface $role = null,
        ResourceInterface $resource = null, $privilege = null
    ) {
        if ($resource instanceof Site) {
            $site = $resource;
        } elseif ($resource instanceof SitePage) {
            $site = $resource->getSite();
        } else {
            // Not a recognized resource.
            return false;
        }

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('user', $role));
        $sitePermission =  $site->getSitePermissions()
            ->matching($criteria)->first();
        if (!$sitePermission) {
            // This user has no site permission
            return false;
        }
        $userRoleNumber = $this->getRoleNumber($sitePermission->getRole());
        return $userRoleNumber <= $this->roleNumber;
    }

    /**
     * Assign incrementing numbers to site roles, starting at admin.
     *
     * @param string
     * @return int
     */
    public function getRoleNumber($role)
    {
        if (SitePermission::ROLE_ADMIN === $role) {
            return 1;
        }
        if (SitePermission::ROLE_EDITOR === $role) {
            return 2;
        }
        if (SitePermission::ROLE_VIEWER === $role) {
            return 3;
        }
        return 4;
    }
}

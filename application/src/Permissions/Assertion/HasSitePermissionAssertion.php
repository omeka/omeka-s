<?php
namespace Omeka\Permissions\Assertion;

use Doctrine\Common\Collections\Criteria;
use Omeka\Entity\Site;
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
        if (method_exists($resource, 'getSite')) {
            $resource = $resource->getSite();
        }
        if (!$resource instanceof Site) {
            return false;
        }

        // Unauthed users can't have any site permissions
        if (!$role) {
            return false;
        }
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('user', $role));
        $sitePermission = $resource->getSitePermissions()
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
        switch ($role) {
            case SitePermission::ROLE_ADMIN:
                return 1;
            case SitePermission::ROLE_EDITOR:
                return 2;
            case SitePermission::ROLE_VIEWER:
            default:
                return 3;
        }
    }
}

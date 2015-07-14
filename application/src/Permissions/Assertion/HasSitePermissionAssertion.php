<?php
namespace Omeka\Permissions\Assertion;

use Doctrine\Common\Collections\Criteria;
use Omeka\Entity\Site;
use Omeka\Entity\SitePage;
use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Assertion\AssertionInterface;
use Zend\Permissions\Acl\Resource\ResourceInterface;
use Zend\Permissions\Acl\Role\RoleInterface;

class HasSitePermissionAssertion implements AssertionInterface
{
    protected $sitePrivileges;

    public function __construct(array $sitePrivileges)
    {
        $this->sitePrivileges = $sitePrivileges;
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
            // This user has no site permissions
            return false;
        }
        foreach ($this->sitePrivileges as $sitePrivilege) {
            if ($sitePermission->hasPrivilege($sitePrivilege)) {
                return true;
            }
        }
        return false;
    }
}

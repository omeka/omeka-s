<?php
namespace Omeka\Permissions\Assertion;

use Omeka\Entity\Site;
use Omeka\Entity\SitePage;
use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Assertion\AssertionInterface;
use Zend\Permissions\Acl\Resource\ResourceInterface;
use Zend\Permissions\Acl\Role\RoleInterface;

class SiteIsPublicAssertion implements AssertionInterface
{
    public function assert(Acl $acl, RoleInterface $role = null,
        ResourceInterface $resource = null, $privilege = null
    ) {
        if ($resource instanceof Site) {
            $site = $resource;
        } elseif ($resource instanceof SitePage) {
            $site = $resource->getSite();
        } else {
            return false;
        }
        return $site->isPublic();
    }
}

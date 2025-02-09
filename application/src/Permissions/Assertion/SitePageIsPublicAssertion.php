<?php
namespace Omeka\Permissions\Assertion;

use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Assertion\AssertionInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;
use Omeka\Entity\SitePage;

class SitePageIsPublicAssertion implements AssertionInterface
{
    public function assert(Acl $acl, RoleInterface $role = null,
        ResourceInterface $resource = null, $privilege = null
    ) {
        if (method_exists($resource, 'getSitePage')) {
            $resource = $resource->getSitePage();
        }
        if (!$resource instanceof SitePage) {
            return false;
        }
        return $resource->isPublic()
            && $resource->getSite()->isPublic();
    }
}

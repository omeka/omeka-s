<?php
namespace Omeka\Permissions\Assertion;

use Omeka\Entity\Site;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Assertion\AssertionInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;

class SiteIsPublicAssertion implements AssertionInterface
{
    public function assert(Acl $acl, RoleInterface $role = null,
        ResourceInterface $resource = null, $privilege = null
    ) {
        if (method_exists($resource, 'getSite')) {
            $resource = $resource->getSite();
        }
        if (!$resource instanceof Site) {
            return false;
        }
        return $resource->isPublic();
    }
}

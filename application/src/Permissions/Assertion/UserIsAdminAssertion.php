<?php
namespace Omeka\Permissions\Assertion;

use Omeka\Entity\User;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Assertion\AssertionInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;

class UserIsAdminAssertion implements AssertionInterface
{
    public function assert(Acl $acl, RoleInterface $role = null,
        ResourceInterface $resource = null, $privilege = null
    ) {
        if (!$resource instanceof User) {
            return false;
        }

        return $acl->isAdminRole($resource->getRole());
    }
}

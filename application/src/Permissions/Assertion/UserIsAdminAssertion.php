<?php
namespace Omeka\Permissions\Assertion;

use Omeka\Entity\User;
use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Assertion\AssertionInterface;
use Zend\Permissions\Acl\Resource\ResourceInterface;
use Zend\Permissions\Acl\Role\RoleInterface;

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

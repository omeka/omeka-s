<?php
namespace Collecting\Permissions\Assertion;

use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Assertion\AssertionInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;

/**
 * Does the user have permission to view a collecting item's user name?
 */
class HasUserNamePermissionAssertion implements AssertionInterface
{
    public function assert(Acl $acl, RoleInterface $role = null,
        ResourceInterface $resource = null, $privilege = null
    ) {
        if ('private' === $resource->getForm()->getAnonType()) {
            // The collecting form restricts user name.
            return false;
        }

        if ($resource->getAnon()) {
            // This item was submitted anonymously.
            return false;
        }

        return true;
    }
}

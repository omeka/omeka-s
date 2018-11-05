<?php
namespace Omeka\Permissions\Assertion;

use Omeka\Entity\Value;
use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Assertion\AssertionInterface;
use Zend\Permissions\Acl\Resource\ResourceInterface;
use Zend\Permissions\Acl\Role\RoleInterface;

class OwnsEntityAssertion implements AssertionInterface
{
    public function assert(Acl $acl, RoleInterface $role = null,
        ResourceInterface $resource = null, $privilege = null
    ) {
        if ($resource instanceof Value) {
            $resource = $resource->getResource();
        }
        return $role && $role === $resource->getOwner();
    }
}

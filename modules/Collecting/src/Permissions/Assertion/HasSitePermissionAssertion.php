<?php
namespace Collecting\Permissions\Assertion;

use Collecting\Entity\CollectingInput;
use Omeka\Permissions\Assertion\HasSitePermissionAssertion as OmekaHasSitePermissionAssertion;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;

class HasSitePermissionAssertion extends OmekaHasSitePermissionAssertion
{
    public function assert(Acl $acl, RoleInterface $role = null,
        ResourceInterface $resource = null, $privilege = null
    ) {
        if ($resource instanceof CollectingInput) {
            // A collecting input inherits the site from its collecting item.
            $resource = $resource->getCollectingItem();
        }
        return parent::assert($acl, $role, $resource, $privilege);
    }
}

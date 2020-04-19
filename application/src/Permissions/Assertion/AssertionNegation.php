<?php
namespace Omeka\Permissions\Assertion;

use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Assertion\AssertionInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;

/**
 * Simple wrapper that negates an existing assertion.
 */
class AssertionNegation implements AssertionInterface
{
    /**
     * @var AssertionInterface
     */
    protected $baseAssertion;

    public function __construct(AssertionInterface $baseAssertion)
    {
        $this->baseAssertion = $baseAssertion;
    }

    public function assert(Acl $acl, RoleInterface $role = null,
        ResourceInterface $resource = null, $privilege = null
    ) {
        return !$this->baseAssertion->assert($acl, $role, $resource, $privilege);
    }
}

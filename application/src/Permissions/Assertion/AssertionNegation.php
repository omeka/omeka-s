<?php
namespace Omeka\Permissions\Assertion;

use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Assertion\AssertionInterface;
use Zend\Permissions\Acl\Resource\ResourceInterface;
use Zend\Permissions\Acl\Role\RoleInterface;

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

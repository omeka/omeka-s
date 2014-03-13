<?php
namespace Omeka\Permissions\Assertion;

use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Assertion\AssertionInterface;
use Zend\Permissions\Acl\Resource\ResourceInterface;
use Zend\Permissions\Acl\Role\RoleInterface;

/**
 * Aggregate of all assertions for an ACL rule.
 *
 * All assertions must return true for the rule to apply.
 */
class AssertionAggregate implements AssertionInterface
{
    /**
     * @var array
     */
    protected $assertions = array();

    /**
     * {@inheritDoc}
     */
    public function assert(Acl $acl, RoleInterface $role = null,
        ResourceInterface $resource = null, $privilege = null
    ) {
        // Run each assertion, returning false if encountered.
        foreach ($this->assertions as $assertion) {
            if (!$assertion->assert($acl, $role, $resource, $privilege)) {
                return false;
            }
        }
        // Otherwise, return true if all assertions return true.
        return true;
    }

    /**
     * Add an ACL rule assertion.
     *
     * @param ssertionInterface $assertion
     */
    public function addAssertion(AssertionInterface $assertion)
    {
        $this->assertions[] = $assertion;
    }
}

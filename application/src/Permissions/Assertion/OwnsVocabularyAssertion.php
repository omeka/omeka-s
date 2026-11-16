<?php
namespace Omeka\Permissions\Assertion;

use Omeka\Entity\Vocabulary;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Assertion\AssertionInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;

/**
 * Assert that the user owns a vocabulary, or the vocabulary of a property or
 * resource class.
 *
 * A term's own owner doesn't count: terms belong to their vocabulary, and the
 * terms an update adds to it have no owner.
 */
class OwnsVocabularyAssertion implements AssertionInterface
{
    public function assert(Acl $acl, ?RoleInterface $role = null,
        ?ResourceInterface $resource = null, $privilege = null
    ) {
        if (method_exists($resource, 'getVocabulary')) {
            $resource = $resource->getVocabulary();
        }
        if (!$resource instanceof Vocabulary) {
            return false;
        }
        return (new OwnsEntityAssertion)->assert($acl, $role, $resource, $privilege);
    }
}

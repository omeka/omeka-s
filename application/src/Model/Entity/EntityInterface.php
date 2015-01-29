<?php
namespace Omeka\Model\Entity;

use Zend\Permissions\Acl\Resource\ResourceInterface;

/**
 * Entity interface.
 *
 * Use Doctrine's docblock annotations to specify object-relational mapping
 * metadata.
 *
 * Mutator methods may be used to filter data prior to being set. Other
 * operations (such as validation) should not be performed in the entity, but
 * rather in the corresponding entity API adapter.
 *
 * @link http://docs.doctrine-project.org/en/latest/reference/annotations-reference.html
 */
interface EntityInterface extends ResourceInterface
{
    /**
     * Get the unique identifier.
     *
     * @return mixed
     */
    public function getId();
}

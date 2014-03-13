<?php
namespace Omeka\Model\Entity;

/**
 * Entity interface.
 *
 * Use Doctrine's docblock annotations to specify object-relational mapping
 * metadata.
 *
 * Every property should have corresponding accessor and mutator methods. (The
 * only exception is the entity ID, which should not have a mutator method.)
 * Mutators may be used to filter data prior to being set. Other operations
 * (such as validation) should not be performed in the entity, but rather in the
 * corresponding entity API adapter.
 *
 * @link http://docs.doctrine-project.org/en/latest/reference/annotations-reference.html
 */
interface EntityInterface
{
}

<?php
namespace Omeka\Entity;

use Omeka\Api\ResourceInterface as OmekaApiResourceInterface;
use Zend\Permissions\Acl\Resource\ResourceInterface as ZendAclResourceInterface;

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
interface EntityInterface extends OmekaApiResourceInterface, ZendAclResourceInterface
{
}

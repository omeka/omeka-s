<?php
namespace Omeka\Db\Filter;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetaData;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Filter resource or resource-related entities by visibility.
 *
 * Checks to see if the current user has permission to view resources. In this
 * case a resource is any entity that extends off the Resource entity, that is,
 * Item, ItemSet, and Media.
 *
 * @link http://doctrine-orm.readthedocs.org/en/latest/reference/filters.html
 */
class ResourceVisibilityFilter extends SQLFilter
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

    protected $relatedEntities = [];

    /**
     * Add a related entity.
     *
     * During onBootstrap, modules may set these visibility rules on their
     * resource-related entities by passing the related entity's class name and
     * the name of the foreign key column.
     *
     * @param string $entity
     * @param string $column
     */
    public function addRelatedEntity($entity, $column)
    {
        $this->relatedEntities[$entity] = $column;
    }

    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if ('Omeka\Entity\Resource' === $targetEntity->getName()) {
            return $this->getResourceConstraint($targetTableAlias);
        }

        if (array_key_exists($targetEntity->getName(), $this->relatedEntities)) {
            $constraint = $this->getResourceConstraint('r');
            if ('' !== $constraint) {
                $resourceFk = $this->relatedEntities[$targetEntity->getName()];
                return sprintf(
                    '%1$s.%2$s = (SELECT r.id FROM resource r WHERE (%3$s) AND r.id = %1$s.%2$s)',
                    $targetTableAlias, $resourceFk, $constraint
                );
            }
        }

        return '';
    }

    /**
     * Get the constraint for a resource.
     *
     * @param string $alias
     * @return string
     */
    protected function getResourceConstraint($alias)
    {
        $acl = $this->serviceLocator->get('Omeka\Acl');
        if ($acl->userIsAllowed('Omeka\Entity\Resource', 'view-all')) {
            return '';
        }

        // Users can view public resources they do not own.
        $constraints = ["$alias.is_public = 1"];
        $identity = $this->serviceLocator->get('Omeka\AuthenticationService')->getIdentity();
        if ($identity) {
            // Users can view all resources they own.
            $constraints[] = 'OR';
            $constraints[] = sprintf(
                "$alias.owner_id = %s",
                $this->getConnection()->quote($identity->getId(), Type::INTEGER)
            );
        }

        return implode(' ', $constraints);
    }

    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }
}

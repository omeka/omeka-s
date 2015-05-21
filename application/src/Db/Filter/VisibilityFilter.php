<?php
namespace Omeka\Db\Filter;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetaData;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

/**
 * Check that the current user can view a resource entity.
 *
 * @see http://doctrine-orm.readthedocs.org/en/latest/reference/filters.html
 */
class VisibilityFilter extends SQLFilter
{
    use ServiceLocatorAwareTrait;

    public function addFilterConstraint(ClassMetadata $targetEntity,
        $targetTableAlias
    ) {
        if ('Omeka\Entity\Resource' !== $targetEntity->getName()) {
            return '';
        }

        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        if ($acl->userIsAllowed('Omeka\Entity\Resource', 'view-all')) {
            return '';
        }

        // Users can view public resources they do not own.
        $constraints = array("$targetTableAlias.is_public = 1");
        $identity = $this->getServiceLocator()
            ->get('Omeka\AuthenticationService')->getIdentity();
        if ($identity) {
            // Users can view all resources they own.
            $connection = $this->getServiceLocator()->get('Omeka\Connection');
            $constraints[] = 'OR';
            $constraints[] = sprintf(
                "$targetTableAlias.owner_id = %s",
                $connection->quote($identity->getId(), Type::INTEGER)
            );
        }

       return implode(' ', $constraints);
    }
}

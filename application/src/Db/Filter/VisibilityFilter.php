<?php
namespace Omeka\Db\Filter;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetaData;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Check that the current user can view a resource entity.
 *
 * @see http://doctrine-orm.readthedocs.org/en/latest/reference/filters.html
 */
class VisibilityFilter extends SQLFilter
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

    public function addFilterConstraint(ClassMetadata $targetEntity,
        $targetTableAlias
    ) {
        if ('Omeka\Entity\Resource' !== $targetEntity->getName()) {
            return '';
        }

        $acl = $this->serviceLocator->get('Omeka\Acl');
        if ($acl->userIsAllowed('Omeka\Entity\Resource', 'view-all')) {
            return '';
        }

        // Users can view public resources they do not own.
        $constraints = ["$targetTableAlias.is_public = 1"];
        $identity = $this->serviceLocator->get('Omeka\AuthenticationService')->getIdentity();
        if ($identity) {
            // Users can view all resources they own.
            $constraints[] = 'OR';
            $constraints[] = sprintf(
                "$targetTableAlias.owner_id = %s",
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

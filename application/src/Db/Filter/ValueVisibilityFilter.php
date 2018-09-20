<?php
namespace Omeka\Db\Filter;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetaData;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Filter value entities by visibility.
 *
 * Checks to see if the current user has permission to view values.
 */
class ValueVisibilityFilter extends SQLFilter
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if ('Omeka\Entity\Value' === $targetEntity->getName()) {
            $acl = $this->serviceLocator->get('Omeka\Acl');
            if ($acl->userIsAllowed('Omeka\Entity\Value', 'view-all')) {
                return '';
            }
            // Users can view public values they do not own.
            $constraint = "$targetTableAlias.is_public = 1";
            $identity = $this->serviceLocator->get('Omeka\AuthenticationService')->getIdentity();
            if ($identity) {
                // Users can view all values they own.
                $constraint = sprintf(
                    '%1$s.resource_id = (SELECT r.id FROM resource r WHERE (%2$s OR r.owner_id = %3$s) AND r.id = %1$s.resource_id)',
                    $targetTableAlias,
                    $constraint,
                    $this->getConnection()->quote($identity->getId(), Type::INTEGER)
                );
            }
            return $constraint;
        }
        return '';
    }

    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }
}

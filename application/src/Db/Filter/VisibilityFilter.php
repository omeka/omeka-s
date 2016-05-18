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

    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias) {
        switch ($targetEntity->getName()) {
            case 'Omeka\Entity\Resource':
                return $this->getResourceConstraint($targetTableAlias);
            case 'Omeka\Entity\SiteBlockAttachment':
                // Users can view an attachment only if they have permission to
                // view the attached item.
                $constraint = $this->getResourceConstraint('r');
                if ('' !== $constraint) {
                    $constraint = sprintf(
                        '%1$s.item_id = (SELECT r.id FROM resource r WHERE (%2$s) AND r.id = %1$s.item_id)',
                        $targetTableAlias, $constraint
                    );
                }
                return $constraint;
            default:
                return '';
        }
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

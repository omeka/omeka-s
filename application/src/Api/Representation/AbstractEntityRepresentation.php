<?php
namespace Omeka\Api\Representation;

use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Entity\EntityInterface;

/**
 * Abstract entity representation.
 *
 * Provides functionality for all entity representations.
 */
abstract class AbstractEntityRepresentation extends AbstractResourceRepresentation
{
    public function __construct(EntityInterface $resource, AdapterInterface $adapter)
    {
        parent::__construct($resource, $adapter);
    }

    /**
     * Authorize the current user.
     *
     * Requests access to the entity and to the corresponding adapter. If the
     * current user does not have access to the adapter, we can assume that it
     * does not have access to the entity.
     *
     * @param string $privilege
     * @return bool
     */
    public function userIsAllowed($privilege)
    {
        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        return $acl->userIsAllowed($this->getAdapter(), $privilege)
            && $acl->userIsAllowed($this->resource, $privilege);
    }
}

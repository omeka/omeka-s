<?php
namespace Omeka\Api\Representation;

use Omeka\Api\Exception;
use Omeka\Entity\EntityInterface;

/**
 * Abstract entity representation.
 *
 * Provides functionality for all entity representations.
 */
abstract class AbstractEntityRepresentation extends AbstractResourceRepresentation
{
    /**
     * {@inheritDoc}
     */
    protected function validateData($data)
    {
        if (!$data instanceof EntityInterface) {
            throw new Exception\InvalidArgumentException(
                $this->getTranslator()->translate(sprintf(
                    'Invalid data sent to %s.', get_called_class()
                ))
            );
        }
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
            && $acl->userIsAllowed($this->getData(), $privilege);
    }
}

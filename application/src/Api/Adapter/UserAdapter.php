<?php
namespace Omeka\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Zend\Validator\EmailAddress;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\Message;
use Omeka\Stdlib\ErrorStore;

class UserAdapter extends AbstractEntityAdapter
{
    /**
     * {@inheritDoc}
     */
    protected $sortFields = [
        'id' => 'id',
        'email' => 'email',
        'name' => 'name',
        'created' => 'created',
        'modified' => 'modified',
        'role' => 'role',
    ];

    /**
     * {@inheritDoc}
     */
    public function getResourceName()
    {
        return 'users';
    }

    /**
     * {@inheritDoc}
     */
    public function getRepresentationClass()
    {
        return 'Omeka\Api\Representation\UserRepresentation';
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClass()
    {
        return 'Omeka\Entity\User';
    }

    /**
     * {@inheritDoc}
     */
    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        if ($this->shouldHydrate($request, 'o:name')) {
            $entity->setName($request->getValue('o:name'));
        }
        if ($this->shouldHydrate($request, 'o:email')) {
            $entity->setEmail($request->getValue('o:email'));
        }

        $role = $request->getValue('o:role');
        if ($role && $this->shouldHydrate($request, 'o:role')) {
            $this->authorize($entity, 'change-role');

            // Ask specially for permission to set an admin role
            $acl = $this->getServiceLocator()->get('Omeka\Acl');
            if ($acl->isAdminRole($role)) {
                $this->authorize($entity, 'change-role-admin');
            }

            $entity->setRole($role);
        }

        if ($this->shouldHydrate($request, 'o:is_active')) {
            $isActive = (bool) $request->getValue('o:is_active', $entity->isActive());
            if ($isActive !== $entity->isActive()) {
                $this->authorize($entity, 'activate-user');
                $entity->setIsActive($isActive);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['email'])) {
            $qb->andWhere($qb->expr()->eq(
                "Omeka\Entity\User.email",
                $this->createNamedParameter($qb, $query['email']))
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        if (false == $entity->getName()) {
            $errorStore->addError('o:name', 'The name cannot be empty.'); // @translate
        }

        $email = $entity->getEmail();
        $validator = new EmailAddress();
        if (!$validator->isValid($email)) {
            $errorStore->addValidatorMessages('o:email', $validator->getMessages());
        }
        if (!$this->isUnique($entity, ['email' => $email])) {
            $errorStore->addError('o:email', new Message(
                'The email %s is already taken.', // @translate
                $email
            ));
        }

        if (false == $entity->getRole()) {
            $errorStore->addError('o:role', 'Users must have a role.'); // @translate
        }
    }

    /**
     * {@inheritDoc}
     */
    public function preprocessBatchUpdate(array $data, Request $request)
    {
        $rawData = $request->getContent();

        if (isset($rawData['o:role'])) {
            $data['o:role'] = $rawData['o:role'];
        }
        if (isset($rawData['o:is_active'])) {
            $data['o:is_active'] = $rawData['o:is_active'];
        }

        return $data;
    }
}

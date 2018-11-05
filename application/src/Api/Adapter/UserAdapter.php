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
    protected $sortFields = [
        'id' => 'id',
        'email' => 'email',
        'name' => 'name',
        'created' => 'created',
        'modified' => 'modified',
        'role' => 'role',
    ];

    public function getResourceName()
    {
        return 'users';
    }

    public function getRepresentationClass()
    {
        return \Omeka\Api\Representation\UserRepresentation::class;
    }

    public function getEntityClass()
    {
        return \Omeka\Entity\User::class;
    }

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
        if ($role
            && $role !== $entity->getRole()
            && $this->shouldHydrate($request, 'o:role')
        ) {
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

    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (!empty($query['email'])) {
            $qb->andWhere($qb->expr()->eq(
                "Omeka\Entity\User.email",
                $this->createNamedParameter($qb, $query['email']))
            );
        }

        if (!empty($query['name'])) {
            $qb->andWhere($qb->expr()->eq(
                "Omeka\Entity\User.name",
                $this->createNamedParameter($qb, $query['name']))
            );
        }

        if (!empty($query['role'])) {
            $qb->andWhere($qb->expr()->eq(
                'Omeka\Entity\User.role',
                $this->createNamedParameter($qb, $query['role']))
            );
        }

        if (isset($query['is_active']) && is_numeric($query['is_active'])) {
            $qb->andWhere($qb->expr()->eq(
                'Omeka\Entity\User.isActive',
                $this->createNamedParameter($qb, (bool) $query['is_active']))
            );
        }

        if (!empty($query['site_permission_site_id'])) {
            $sitePermissionAlias = $this->createAlias();
            $qb->innerJoin(
                'Omeka\Entity\SitePermission',
                $sitePermissionAlias,
                'WITH',
                $sitePermissionAlias . '.user = ' . $this->getEntityClass()
            );
            $qb->andWhere($qb->expr()->eq(
                "$sitePermissionAlias.site",
                $this->createNamedParameter($qb, $query['site_permission_site_id']))
            );
        }
    }

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

    public function batchUpdate(Request $request)
    {
        $this->unsetCurrentUserFromBatch($request);
        return parent::batchUpdate($request);
    }

    public function batchDelete(Request $request)
    {
        $this->unsetCurrentUserFromBatch($request);
        return parent::batchDelete($request);
    }

    /**
     * Remove the current user before a batch process.
     *
     * @param Request $request
     */
    protected function unsetCurrentUserFromBatch(Request $request)
    {
        $services = $this->getServiceLocator();
        $ids = $request->getIds();
        $ids = array_filter(array_unique(array_map('intval', $ids)));
        $identity = $services->get('ControllerPluginManager')->get('identity');
        $userId = $identity()->getId();
        $key = array_search($userId, $ids);
        if ($key !== false) {
            $logger = $services->get('Omeka\Logger');
            $logger->warn(
                new Message(
                    'The current user #%d was removed from the batch process.', // @translate
                    $userId
                )
            );
            unset($ids[$key]);
        }
        $request->setIds($ids);
    }

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

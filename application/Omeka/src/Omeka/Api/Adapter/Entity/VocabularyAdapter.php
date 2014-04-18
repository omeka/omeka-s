<?php
namespace Omeka\Api\Adapter\Entity;

use Doctrine\ORM\QueryBuilder;
use Omeka\Model\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;
use Omeka\Validator\Db\IsUnique;

class VocabularyAdapter extends AbstractEntityAdapter
{
    public function getEntityClass()
    {
        return 'Omeka\Model\Entity\Vocabulary';
    }

    public function hydrate(array $data, $entity)
    {
        if (isset($data['owner']['id'])) {
            $owner = $this->getEntityManager()
                ->getRepository('Omeka\Model\Entity\User')
                ->find($data['owner']['id']);
            $entity->setOwner($owner);
        }
        if (isset($data['namespace_uri'])) {
            $entity->setNamespaceUri($data['namespace_uri']);
        }
        if (isset($data['label'])) {
            $entity->setLabel($data['label']);
        }
        if (isset($data['comment'])) {
            $entity->setComment($data['comment']);
        }
    }

    public function extract($entity)
    {
        return array(
            '@id'           => $this->getApiUrl($entity),
            'id'            => $entity->getId(),
            'namespace_uri' => $entity->getNamespaceUri(),
            'label'         => $entity->getLabel(),
            'comment'       => $entity->getComment(),
            'owner'         => $this->extractEntity(
                $entity->getOwner(),
                $this->getAdapter('users')
            ),
        );
    }

    public function buildQuery(array $query, QueryBuilder $qb)
    {
        if (isset($query['owner']['id'])) {
            $this->joinWhere($qb, new UserAdapter, 'owner',
                'id', $query['owner']['id']);
        }
        if (isset($query['namespace_uri'])) {
            $this->andWhere($qb, 'namespaceUri', $query['namespace_uri']);
        }
    }

    public function validate(EntityInterface $entity, ErrorStore $errorStore,
        $isPersistent
    ) {
        $validator = new IsUnique(array('namespaceUri'), $this->getEntityManager());
        if (!$validator->isValid($entity)) {
            $errorStore->addValidatorMessages('namespace_uri', $validator->getMessages());
        }
        if (null === $entity->getNamespaceUri()) {
            $errorStore->addError('namespace_uri', 'The namespace_uri field cannot be null.');
        }
        if (null === $entity->getLabel()) {
            $errorStore->addError('label', 'The label field cannot be null.');
        }
    }
}

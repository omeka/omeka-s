<?php
namespace Omeka\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Entity\Vocabulary;
use Omeka\Stdlib\ErrorStore;
use Omeka\Stdlib\Message;

class ResourceClassAdapter extends AbstractEntityAdapter
{
    /**
     * {@inheritDoc}
     */
    protected $sortFields = [
        'id' => 'id',
        'local_name' => 'localName',
        'label' => 'label',
        'comment' => 'comment',
    ];

    /**
     * {@inheritDoc}
     */
    public function getResourceName()
    {
        return 'resource_classes';
    }

    /**
     * {@inheritDoc}
     */
    public function getRepresentationClass()
    {
        return 'Omeka\Api\Representation\ResourceClassRepresentation';
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClass()
    {
        return 'Omeka\Entity\ResourceClass';
    }

    /**
     * {@inheritDoc}
     */
    public function sortQuery(QueryBuilder $qb, array $query)
    {
        if (is_string($query['sort_by'])) {
            if ('item_count' == $query['sort_by']) {
                $this->sortByCount($qb, $query, 'resources', 'Omeka\Entity\Item');
            } else {
                parent::sortQuery($qb, $query);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        $data = $request->getContent();
        $this->hydrateOwner($request, $entity);

        if ($this->shouldHydrate($request, 'o:local_name')) {
            $entity->setLocalName($request->getValue('o:local_name'));
        }
        if ($this->shouldHydrate($request, 'o:label')) {
            $entity->setLabel($request->getValue('o:label'));
        }
        if ($this->shouldHydrate($request, 'o:comment')) {
            $entity->setComment($request->getValue('o:comment'));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['owner_id'])) {
            $userAlias = $this->createAlias();
            $qb->innerJoin(
                'Omeka\Entity\ResourceClass.owner',
                $userAlias
            );
            $qb->andWhere($qb->expr()->eq(
                "$userAlias.id",
                $this->createNamedParameter($qb, $query['owner_id']))
            );
        }
        if (isset($query['vocabulary_id'])) {
            $vocabularyAlias = $this->createAlias();
            $qb->innerJoin(
                'Omeka\Entity\ResourceClass.vocabulary',
                $vocabularyAlias
            );
            $qb->andWhere($qb->expr()->eq(
                "$vocabularyAlias.id",
                $this->createNamedParameter($qb, $query['vocabulary_id']))
            );
        }
        if (isset($query['vocabulary_namespace_uri'])) {
            $vocabularyAlias = $this->createAlias();
            $qb->innerJoin(
                'Omeka\Entity\ResourceClass.vocabulary',
                $vocabularyAlias
            );
            $qb->andWhere($qb->expr()->eq(
                "$vocabularyAlias.namespaceUri",
                $this->createNamedParameter($qb, $query['vocabulary_namespace_uri']))
            );
        }
        if (isset($query['vocabulary_prefix'])) {
            $vocabularyAlias = $this->createAlias();
            $qb->innerJoin(
                'Omeka\Entity\ResourceClass.vocabulary',
                $vocabularyAlias
            );
            $qb->andWhere($qb->expr()->eq(
                "$vocabularyAlias.prefix",
                $this->createNamedParameter($qb, $query['vocabulary_prefix']))
            );
        }
        if (isset($query['local_name'])) {
            $qb->andWhere($qb->expr()->eq(
                "Omeka\Entity\ResourceClass.localName",
                $this->createNamedParameter($qb, $query['local_name']))
            );
        }
        if (isset($query['term']) && $this->isTerm($query['term'])) {
            list($prefix, $localName) = explode(':', $query['term']);
            $vocabularyAlias = $this->createAlias();
            $qb->innerJoin(
                'Omeka\Entity\ResourceClass.vocabulary',
                $vocabularyAlias
            );
            $qb->andWhere($qb->expr()->eq(
                "$vocabularyAlias.prefix",
                $this->createNamedParameter($qb, $prefix))
            );
            $qb->andWhere($qb->expr()->eq(
                "Omeka\Entity\ResourceClass.localName",
                $this->createNamedParameter($qb, $localName))
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        // Validate local name
        if (false == $entity->getLocalName()) {
            $errorStore->addError('o:local_name', 'The local name cannot be empty.'); // @translate
        }

        // Validate label
        if (false == $entity->getLabel()) {
            $errorStore->addError('o:label', 'The label cannot be empty.'); // @translate
        }

        // Validate vocabulary
        if ($entity->getVocabulary() instanceof Vocabulary) {
            if ($entity->getVocabulary()->getId()) {
                // Vocabulary is persistent. Check for unique local name.
                $criteria = [
                    'vocabulary' => $entity->getVocabulary(),
                    'localName' => $entity->getLocalName(),
                ];
                if (!$this->isUnique($entity, $criteria)) {
                    $errorStore->addError('o:local_name', new Message(
                        'The local name "%s" is already taken.', // @translate
                        $entity->getLocalName()
                    ));
                }
            }
        } else {
            $errorStore->addError('o:vocabulary', 'A vocabulary must be set.'); // @translate
        }
    }
}

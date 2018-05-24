<?php
namespace Omeka\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Exception;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class ItemSetAdapter extends AbstractResourceEntityAdapter
{
    /**
     * {@inheritDoc}
     */
    protected $sortFields = [
        'id' => 'id',
        'created' => 'created',
        'modified' => 'modified',
    ];

    /**
     * {@inheritDoc}
     */
    public function getResourceName()
    {
        return 'item_sets';
    }

    /**
     * {@inheritDoc}
     */
    public function getRepresentationClass()
    {
        return 'Omeka\Api\Representation\ItemSetRepresentation';
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClass()
    {
        return 'Omeka\Entity\ItemSet';
    }

    /**
     * {@inheritDoc}
     */
    public function buildQuery(QueryBuilder $qb, array $query)
    {
        parent::buildQuery($qb, $query);

        if (isset($query['id'])) {
            $qb->andWhere($qb->expr()->eq('Omeka\Entity\ItemSet.id', $query['id']));
        }

        // Select item sets to which the current user can assign an item.
        if (isset($query['is_open'])) {
            $acl = $this->getServiceLocator()->get('Omeka\Acl');
            if (!$acl->userIsAllowed('Omeka\Entity\ItemSet', 'view-all')) {
                $expr = $qb->expr()->eq(
                    'Omeka\Entity\ItemSet.isOpen',
                    $qb->expr()->literal(true)
                );
                $identity = $this->getServiceLocator()
                    ->get('Omeka\AuthenticationService')->getIdentity();
                if ($identity) {
                    $expr = $qb->expr()->orX(
                        $expr,
                        $qb->expr()->eq(
                            'Omeka\Entity\ItemSet.owner',
                            $this->createNamedParameter($qb, $identity->getId())
                        )
                    );
                }
                $qb->andWhere($expr);
            }
        }

        if (!empty($query['site_id'])) {
            $siteAdapter = $this->getAdapter('sites');
            try {
                $site = $siteAdapter->findEntity($query['site_id']);
            } catch (Exception\NotFoundException $e) {
                $site = null;
            }
            $siteItemSetsAlias = $this->createAlias();
            $qb->innerJoin(
                'Omeka\Entity\ItemSet.siteItemSets',
                $siteItemSetsAlias
            );
            $qb->andWhere($qb->expr()->eq(
                "$siteItemSetsAlias.site",
                $this->createNamedParameter($qb, $query['site_id']))
            );
            $qb->addOrderBy("$siteItemSetsAlias.position", 'ASC');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function sortQuery(QueryBuilder $qb, array $query)
    {
        if (is_string($query['sort_by'])) {
            if ('item_count' == $query['sort_by']) {
                $this->sortByCount($qb, $query, 'items');
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
        parent::hydrate($request, $entity, $errorStore);

        if ($this->shouldHydrate($request, 'o:is_open')) {
            $entity->setIsOpen($request->getValue('o:is_open'));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function preprocessBatchUpdate(array $data, Request $request)
    {
        $rawData = $request->getContent();
        $data = parent::preprocessBatchUpdate($data, $request);

        if (isset($rawData['o:is_open'])) {
            $data['o:is_open'] = $rawData['o:is_open'];
        }

        return $data;
    }
}

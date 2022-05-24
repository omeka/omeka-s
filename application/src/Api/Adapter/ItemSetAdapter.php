<?php
namespace Omeka\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Exception;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class ItemSetAdapter extends AbstractResourceEntityAdapter
{
    protected $sortFields = [
        'id' => 'id',
        'created' => 'created',
        'modified' => 'modified',
        'title' => 'title',
    ];

    protected $scalarFields = [
        'id' => 'id',
        'title' => 'title',
        'created' => 'created',
        'modified' => 'modified',
        'is_public' => 'isPublic',
        'thumbnail' => 'thumbnail',
        'is_open' => 'isOpen',
    ];

    /**
     * Alias of query builder for join clause between `site` and `item_sets`.
     * @var string
     */
    protected $siteItemSetsAlias;

    public function getResourceName()
    {
        return 'item_sets';
    }

    public function getRepresentationClass()
    {
        return \Omeka\Api\Representation\ItemSetRepresentation::class;
    }

    public function getEntityClass()
    {
        return \Omeka\Entity\ItemSet::class;
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
        $this->siteItemSetsAlias = null;

        parent::buildQuery($qb, $query);

        // Select item sets to which the current user can assign an item.
        if (isset($query['is_open'])) {
            $acl = $this->getServiceLocator()->get('Omeka\Acl');
            if (!$acl->userIsAllowed('Omeka\Entity\ItemSet', 'view-all')) {
                $expr = $qb->expr()->eq(
                    'omeka_root.isOpen',
                    $qb->expr()->literal(true)
                );
                $identity = $this->getServiceLocator()
                    ->get('Omeka\AuthenticationService')->getIdentity();
                if ($identity) {
                    $expr = $qb->expr()->orX(
                        $expr,
                        $qb->expr()->eq(
                            'omeka_root.owner',
                            $this->createNamedParameter($qb, $identity->getId())
                        )
                    );
                }
                $qb->andWhere($expr);
            }
        }

        if (isset($query['site_id']) && is_numeric($query['site_id'])) {
            $siteAdapter = $this->getAdapter('sites');
            // Though $site isn't used here, this is intended to ensure that the
            // user cannot perform a query against a private site he doesn't
            // have access to.
            try {
                $site = $siteAdapter->findEntity($query['site_id']);
            } catch (Exception\NotFoundException $e) {
                $site = null;
            }
            $this->siteItemSetsAlias = $this->createAlias();
            $qb->innerJoin(
                'omeka_root.siteItemSets',
                $this->siteItemSetsAlias
            );
            $qb->andWhere($qb->expr()->eq(
                "$this->siteItemSetsAlias.site",
                $this->createNamedParameter($qb, $query['site_id']))
            );
        } elseif (isset($query['in_sites']) && (is_numeric($query['in_sites']) || is_bool($query['in_sites']))) {
            $siteItemSetsAlias = $this->createAlias();
            if ($query['in_sites']) {
                $qb->innerJoin('omeka_root.siteItemSets', $siteItemSetsAlias);
            } else {
                $qb->leftJoin('omeka_root.siteItemSets', $siteItemSetsAlias);
                $qb->andWhere($qb->expr()->isNull($siteItemSetsAlias));
            }
        }
    }

    public function sortQuery(QueryBuilder $qb, array $query)
    {
        if (is_string($query['sort_by'])) {
            if ('item_count' == $query['sort_by']) {
                $this->sortByCount($qb, $query, 'items');
            } else {
                parent::sortQuery($qb, $query);
            }
        }
        //In site view, sorting by admin-defined position
        if (isset($this->siteItemSetsAlias)) {
            $qb->addOrderBy("$this->siteItemSetsAlias.position", 'ASC');
        }
    }

    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        parent::hydrate($request, $entity, $errorStore);

        if ($this->shouldHydrate($request, 'o:is_open')) {
            $entity->setIsOpen($request->getValue('o:is_open'));
        }
    }

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

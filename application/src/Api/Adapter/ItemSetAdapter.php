<?php
namespace Omeka\Api\Adapter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Exception;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Entity\SiteItemSet;
use Omeka\Stdlib\ErrorStore;

class ItemSetAdapter extends AbstractResourceEntityAdapter
{
    protected $sortFields = [
        'id' => 'id',
        'created' => 'created',
        'modified' => 'modified',
        'title' => 'title',
        'is_open' => 'isOpen',
    ];

    protected $scalarFields = [
        'id' => 'id',
        'title' => 'title',
        'created' => 'created',
        'modified' => 'modified',
        'is_public' => 'isPublic',
        'thumbnail' => 'thumbnail',
        'is_open' => 'isOpen',
        'owner' => 'owner',
        'resource_class' => 'resourceClass',
        'resource_template' => 'resourceTemplate',
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

        $isCreate = Request::CREATE === $request->getOperation();
        $isUpdate = Request::UPDATE === $request->getOperation();
        $isPartial = $isUpdate && $request->getOption('isPartial');
        $append = $isPartial && 'append' === $request->getOption('collectionAction');
        $remove = $isPartial && 'remove' === $request->getOption('collectionAction');

        if ($this->shouldHydrate($request, 'o:is_open')) {
            $entity->setIsOpen($request->getValue('o:is_open'));
        }

        // For now, use the same key "assign_new_items". In fact, there should be two
        // columns (or a site setting) or one "assign_new_resources". The same for acl.

        // Similar to ItemAdapter and SiteAdapter, except getSites() is getSiteItemSets()
        // and subsequent differences.
        if ($isCreate && !is_array($request->getValue('o:site'))) {
            // On CREATE and when no "o:site" array is passed, assign this item
            // to all sites where assignNewItems=true.
            $dql = '
                SELECT site
                FROM Omeka\Entity\Site site
                WHERE site.assignNewItems = true';
            $entityManager = $this->getEntityManager();
            $query = $entityManager->createQuery($dql);
            $siteItemSets = $entity->getSiteItemSets();
            $position = 1;
            foreach ($query->getResult() as $site) {
                $siteItemSet = new SiteItemSet;
                $siteItemSet->setSite($site);
                $siteItemSet->setItemSet($entity);
                $siteItemSet->setPosition($position++);
                $siteItemSets->add($siteItemSet);
                $entityManager->persist($siteItemSet);
            }
        } elseif ($this->shouldHydrate($request, 'o:site')) {
            $entityManager = $this->getEntityManager();
            $acl = $this->getServiceLocator()->get('Omeka\Acl');
            $sitesData = $request->getValue('o:site', []);
            $siteAdapter = $this->getAdapter('sites');
            $siteItemSets = $entity->getSiteItemSets();
            $sitesToRetain = [];

            foreach ($sitesData as $siteData) {
                if (is_array($siteData) && isset($siteData['o:id'])) {
                    $siteId = $siteData['o:id'];
                } elseif (is_numeric($siteData)) {
                    $siteId = $siteData;
                } else {
                    continue;
                }
                $site = $siteAdapter->findEntity($siteId);
                if (!$site) {
                    continue;
                }
                $criteria = Criteria::create()->where(Criteria::expr()->eq('site', $site));
                $siteItemSet = $siteItemSets->matching($criteria)->first();
                if ($remove) {
                    if ($siteItemSet && $acl->userIsAllowed($site, 'can-assign-items')) {
                        $siteItemSets->removeElement($siteItemSet);
                        $entityManager->remove($siteItemSet);
                    }
                    continue;
                }
                // Assign site that was not already assigned.
                if (!$siteItemSet && $acl->userIsAllowed($site, 'can-assign-items')) {
                    $siteItemSet = new SiteItemSet;
                    $siteItemSet->setSite($site);
                    $siteItemSet->setItemSet($entity);
                    $siteItemSet->setPosition($siteItemSets->count() + 1);
                    $siteItemSets->add($siteItemSet);
                    $entityManager->persist($siteItemSet);
                }
                $sitesToRetain[] = $site;
            }

            if (!$append && !$remove) {
                // Remove sites that were not included in the passed data.
                $criteria = Criteria::create()->where(Criteria::expr()->notIn('site', $sitesToRetain));
                foreach ($siteItemSets->matching($criteria) as $siteItemSet) {
                    $site = $siteItemSet->getSite();
                    if ($acl->userIsAllowed($site, 'can-assign-items')) {
                        $siteItemSets->removeElement($siteItemSet);
                        $entityManager->remove($siteItemSet);
                    }
                }
            }
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

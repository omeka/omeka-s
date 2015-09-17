<?php
namespace Omeka\Api\Adapter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Zend\Validator\EmailAddress;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Entity\SitePermission;
use Omeka\Stdlib\ErrorStore;

class SiteAdapter extends AbstractEntityAdapter
{
    /**
     * {@inheritDoc}
     */
    public function getResourceName()
    {
        return 'sites';
    }

    /**
     * {@inheritDoc}
     */
    public function getRepresentationClass()
    {
        return 'Omeka\Api\Representation\SiteRepresentation';
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClass()
    {
        return 'Omeka\Entity\Site';
    }

    /**
     * {@inheritDoc}
     */
    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        $this->hydrateOwner($request, $entity);
        if ($this->shouldHydrate($request, 'o:slug')) {
            $entity->setSlug($request->getValue('o:slug'));
        }
        if ($this->shouldHydrate($request, 'o:theme')) {
            $entity->setTheme($request->getValue('o:theme'));
        }
        if ($this->shouldHydrate($request, 'o:title')) {
            $entity->setTitle($request->getValue('o:title'));
        }
        if ($this->shouldHydrate($request, 'o:navigation')) {
            $entity->setNavigation($request->getValue('o:navigation', array()));
        }
        if ($this->shouldHydrate($request, 'o:is_public')) {
            $entity->setIsPublic($request->getValue('o:is_public', true));
        }

        if ($this->shouldHydrate($request, 'o:page')) {
            $pagesData = $request->getValue('o:page', array());
            $adapter = $this->getAdapter('site_pages');
            $retainPages = array();
            foreach ($pagesData as $pageData) {
                if (isset($pageData['o:id'])) {
                    $page = $adapter->findEntity($pageData['o:id']);
                    $retainPages[] = $page;
                }
            }

            $pages = $entity->getPages();
            // Remove pages not included in request.
            foreach ($pages as $page) {
                if (!in_array($page, $retainPages, true)) {
                    $pages->removeElement($page);
                }
            }
        }

        $sitePermissionsData = $request->getValue('o:site_permission');
        if ($this->shouldHydrate($request, 'o:site_permission')
            && is_array($sitePermissionsData)
        ) {

            $userAdapter = $this->getAdapter('users');
            $sitePermissions = $entity->getSitePermissions();
            $sitePermissionsToRetain = array();

            foreach ($sitePermissionsData as $sitePermissionData) {

                if (!isset($sitePermissionData['o:user']['o:id'])) {
                    continue;
                }
                if (!isset($sitePermissionData['o:role'])) {
                    continue;
                }

                $user = $userAdapter->findEntity($sitePermissionData['o:user']['o:id']);
                $criteria = Criteria::create()
                    ->where(Criteria::expr()->eq('user', $user));
                $sitePermission = $sitePermissions->matching($criteria)->first();

                if (!$sitePermission) {
                    $sitePermission = new SitePermission;
                    $sitePermission->setSite($entity);
                    $sitePermission->setUser($user);
                    $entity->getSitePermissions()->add($sitePermission);
                }

                $sitePermission->setRole($sitePermissionData['o:role']);
                $sitePermissionsToRetain[] = $sitePermission;
            }
            foreach ($sitePermissions as $sitePermissionId => $sitePermission) {
                if (!in_array($sitePermission, $sitePermissionsToRetain)) {
                    $sitePermissions->remove($sitePermissionId);
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        $slug = $entity->getSlug();
        if (!is_string($slug) || $slug === '') {
            $errorStore->addError('o:slug', 'The slug cannot be empty.');
        }
        if (preg_match('/[^a-zA-Z0-9-]/u', $slug)) {
            $errorStore->addError('o:slug',
                'A slug can only contain letters, numbers, and hyphens.');
        }
        if (!$this->isUnique($entity, array('slug' => $slug))) {
            $errorStore->addError('o:slug', sprintf(
                'The slug "%s" is already taken.',
                $slug
            ));
        }

        if (false == $entity->getTitle()) {
            $errorStore->addError('o:title', 'A site must have a title.');
        }

        if (false == $entity->getTheme()) {
            $errorStore->addError('o:theme', 'A site must have a theme.');
        }

        if (!is_array($entity->getNavigation())) {
            $errorStore->addError('o:navigation', 'A site must have navigation data.');
        }
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['owner_id'])) {
            $userAlias = $this->createAlias();
            $qb->innerJoin(
                'Omeka\Entity\Site.owner',
                $userAlias
            );
            $qb->andWhere($qb->expr()->eq(
                "$userAlias.id",
                $this->createNamedParameter($qb, $query['owner_id']))
            );
        }
    }
}

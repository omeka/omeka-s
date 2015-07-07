<?php
namespace Omeka\Api\Adapter;

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
        if ($this->shouldHydrate($request, 'o:site_permission')) {
            $getSitePermission = function ($userId, $sitePermissions) {
                foreach ($sitePermissions as $sitePermission) {
                    if ($userId == $sitePermission->getUser()->getId()) {
                        return $sitePermission;
                    }
                }
                return null;
            };
            $userAdapter = $this->getAdapter('users');
            $sitePermissions = $entity->getSitePermissions();
            $sitePermissionsToRetain = array();
            $sitePermissionsData = $request->getValue('o:site_permission', array());
            foreach ($sitePermissionsData as $sitePermissionData) {
                if (!isset($sitePermissionData['o:user']['o:id'])) {
                    continue;
                }
                $userId = $sitePermissionData['o:user']['o:id'];
                $admin = isset($sitePermissionData['o:admin'])
                    ? $sitePermissionData['o:admin'] : null;
                $attach = isset($sitePermissionData['o:attach'])
                    ? $sitePermissionData['o:attach'] : null;
                $edit = isset($sitePermissionData['o:edit'])
                    ? $sitePermissionData['o:edit'] : null;
                $sitePermission = $getSitePermission($userId, $sitePermissions);
                if (!$sitePermission) {
                    $user = $userAdapter->findEntity($userId);
                    $sitePermission = new SitePermission;
                    $sitePermission->setSite($entity);
                    $sitePermission->setUser($user);
                    $entity->getSitePermissions()->add($sitePermission);
                }
                $sitePermission->setAdmin($admin);
                $sitePermission->setAttach($attach);
                $sitePermission->setEdit($edit);
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
        if (preg_match('/[^a-zA-Z0-9\/-]/u', $slug)) {
            $errorStore->addError('o:slug',
                'A slug can only contain letters, numbers, slashes, and hyphens.');
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
}

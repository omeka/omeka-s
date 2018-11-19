<?php
namespace Omeka;

use Omeka\Module\AbstractModule;
use Zend\EventManager\Event as ZendEvent;
use Zend\EventManager\SharedEventManagerInterface;

/**
 * The Omeka module.
 */
class Module extends AbstractModule
{
    /**
     * This Omeka version.
     */
    const VERSION = '1.3.0';

    /**
     * The vocabulary IRI used to define Omeka application data.
     */
    const OMEKA_VOCABULARY_IRI = 'http://omeka.org/s/vocabs/o#';

    /**
     * The JSON-LD term that expands to the vocabulary IRI.
     */
    const OMEKA_VOCABULARY_TERM = 'o';

    public function getConfig()
    {
        return array_merge(
            include __DIR__ . '/config/module.config.php',
            include __DIR__ . '/config/routes.config.php',
            include __DIR__ . '/config/navigation.config.php'
        );
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $sharedEventManager->attach(
            'Zend\View\Helper\Navigation\AbstractHelper',
            'isAllowed',
            [$this, 'navigationPageIsAllowed']
        );

        $sharedEventManager->attach(
            'Omeka\Entity\Media',
            'entity.remove.post',
            [$this, 'deleteMediaFiles']
        );

        $sharedEventManager->attach(
            'Omeka\Api\Representation\MediaRepresentation',
            'rep.resource.json',
            [$this, 'filterHtmlMediaJsonLd']
        );

        $sharedEventManager->attach(
            'Omeka\Api\Representation\MediaRepresentation',
            'rep.resource.json',
            [$this, 'filterYoutubeMediaJsonLd']
        );

        $sharedEventManager->attach(
            'Omeka\Api\Adapter\MediaAdapter',
            'api.search.query',
            [$this, 'filterMedia']
        );

        $sharedEventManager->attach(
            'Omeka\Api\Adapter\MediaAdapter',
            'api.find.query',
            [$this, 'filterMedia']
        );

        $sharedEventManager->attach(
            'Omeka\Api\Adapter\SiteAdapter',
            'api.search.query',
            [$this, 'filterSites']
        );

        $sharedEventManager->attach(
            'Omeka\Api\Adapter\SiteAdapter',
            'api.find.query',
            [$this, 'filterSites']
        );

        $sharedEventManager->attach(
            '*',
            'api.context',
            [$this, 'addTermDefinitionsToContext']
        );

        $sharedEventManager->attach(
            '*',
            'sql_filter.resource_visibility',
            function (ZendEvent $event) {
                // Users can view block attachments only if they have permission
                // to view the attached item.
                $relatedEntities = $event->getParam('relatedEntities');
                $relatedEntities['Omeka\Entity\SiteBlockAttachment'] = 'item_id';
                $event->setParam('relatedEntities', $relatedEntities);
            }
        );

        $resources = [
            'Omeka\Controller\Admin\Item',
            'Omeka\Controller\Admin\ItemSet',
            'Omeka\Controller\Admin\Media',
            'Omeka\Controller\Site\Item',
            'Omeka\Controller\Site\Media',
        ];
        foreach ($resources as $resource) {
            $sharedEventManager->attach(
                $resource,
                'view.show.after',
                function (ZendEvent $event) {
                    $view = $event->getTarget();
                    if (($view->status()->isAdminRequest() && !$view->setting('disable_jsonld_embed'))
                        || ($view->status()->isSiteRequest() && !$view->siteSetting('disable_jsonld_embed'))
                    ) {
                        echo $view->resource->embeddedJsonLd();
                    }
                }
            );
            $sharedEventManager->attach(
                $resource,
                'view.browse.after',
                function (ZendEvent $event) {
                    $view = $event->getTarget();
                    if (($view->status()->isAdminRequest() && !$view->setting('disable_jsonld_embed'))
                        || ($view->status()->isSiteRequest() && !$view->siteSetting('disable_jsonld_embed'))
                    ) {
                        foreach ($view->resources as $resource) {
                            echo $resource->embeddedJsonLd();
                        }
                    }
                }
            );
        }
    }

    /**
     * Add term definitions to the JSON-LD context.
     *
     * Adds the Omeka, vocabulary, and any other term definitions.
     *
     * @param ZendEvent $event
     */
    public function addTermDefinitionsToContext(ZendEvent $event)
    {
        $context = $event->getParam('context');
        $context[self::OMEKA_VOCABULARY_TERM] = self::OMEKA_VOCABULARY_IRI;
        $stmt = $this->getServiceLocator()
            ->get('Omeka\Connection')
            ->query('SELECT * FROM vocabulary;');
        while ($row = $stmt->fetch()) {
            $context[$row['prefix']] = [
                '@id' => $row['namespace_uri'],
                'vocabulary_id' => $row['id'],
                'vocabulary_label' => $row['label'],
            ];
        }
        $context['o-cnt'] = 'http://www.w3.org/2011/content#';
        $context['o-time'] = 'http://www.w3.org/2006/time#';
        $event->setParam('context', $context);
    }

    /**
     * Determine whether a navigation page is allowed.
     *
     * @param ZendEvent $event
     * @return bool
     */
    public function navigationPageIsAllowed(ZendEvent $event)
    {
        $accepted = true;
        $params = $event->getParams();
        $acl = $params['acl'];
        $page = $params['page'];

        if (!$acl) {
            return $accepted;
        }

        $resource = $page->getResource();
        $privilege = $page->getPrivilege();

        if ($resource || $privilege) {
            $accepted = $acl->hasResource($resource)
                && $acl->userIsAllowed($resource, $privilege);
        }

        $event->stopPropagation();
        return $accepted;
    }

    /**
     * Delete all files associated with a removed Media entity.
     *
     * @param ZendEvent $event
     */
    public function deleteMediaFiles(ZendEvent $event)
    {
        $media = $event->getTarget();
        $store = $this->getServiceLocator()->get('Omeka\File\Store');
        $thumbnailManager = $this->getServiceLocator()->get('Omeka\File\ThumbnailManager');

        if ($media->hasOriginal()) {
            $storagePath = sprintf('original/%s', $media->getFilename());
            $store->delete($storagePath);
        }

        if ($media->hasThumbnails()) {
            foreach ($thumbnailManager->getTypes() as $type) {
                $storagePath = sprintf('%s/%s.jpg', $type, $media->getStorageId());
                $store->delete($storagePath);
            }
        }
    }

    /**
     * Filter the JSON-LD for HTML media.
     *
     * @param ZendEvent $event
     */
    public function filterHtmlMediaJsonLd(ZendEvent $event)
    {
        if ('html' !== $event->getTarget()->ingester()) {
            return;
        }
        $data = $event->getTarget()->mediaData();
        $jsonLd = $event->getParam('jsonLd');
        $jsonLd['@type'] = 'o-cnt:ContentAsText';
        $jsonLd['o-cnt:chars'] = $data['html'];
        $jsonLd['o-cnt:characterEncoding'] = 'UTF-8';
        $event->setParam('jsonLd', $jsonLd);
    }

    /**
     * Filter the JSON-LD for YouTube media.
     *
     * @param ZendEvent $event
     */
    public function filterYoutubeMediaJsonLd(ZendEvent $event)
    {
        if ('youtube' !== $event->getTarget()->ingester()) {
            return;
        }
        $data = $event->getTarget()->mediaData();
        $jsonLd = $event->getParam('jsonLd');
        if (isset($data['start']) || isset($data['end'])) {
            if (isset($data['start'])) {
                $jsonLd['o-time:hasBeginning'] = [
                    '@value' => $data['start'],
                    '@type' => 'o-time:seconds',
                ];
            }
            if (isset($data['end'])) {
                $jsonLd['o-time:hasEnd'] = [
                    '@value' => $data['end'],
                    '@type' => 'o-time:seconds',
                ];
            }
        }
        $event->setParam('jsonLd', $jsonLd);
    }

    /**
     * Filter media belonging to private items.
     *
     * @param ZendEvent $event
     */
    public function filterMedia(ZendEvent $event)
    {
        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        if ($acl->userIsAllowed('Omeka\Entity\Resource', 'view-all')) {
            return;
        }

        $adapter = $event->getTarget();
        $itemAlias = $adapter->createAlias();
        $qb = $event->getParam('queryBuilder');
        $qb->innerJoin('Omeka\Entity\Media.item', $itemAlias);

        // Users can view media they do not own that belong to public items.
        $expression = $qb->expr()->eq("$itemAlias.isPublic", true);

        $identity = $this->getServiceLocator()
            ->get('Omeka\AuthenticationService')->getIdentity();
        if ($identity) {
            // Users can view all media they own.
            $expression = $qb->expr()->orX(
                $expression,
                $qb->expr()->eq(
                    "$itemAlias.owner",
                    $adapter->createNamedParameter($qb, $identity)
                )
            );
        }
        $qb->andWhere($expression);
    }

    /**
     * Filter private sites.
     *
     * @param ZendEvent $event
     */
    public function filterSites(ZendEvent $event)
    {
        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        if ($acl->userIsAllowed('Omeka\Entity\Site', 'view-all')) {
            return;
        }

        $adapter = $event->getTarget();
        $qb = $event->getParam('queryBuilder');

        // Users can view sites they do not own that are public.
        $expression = $qb->expr()->eq("Omeka\Entity\Site.isPublic", true);

        $identity = $this->getServiceLocator()
            ->get('Omeka\AuthenticationService')->getIdentity();
        if ($identity) {
            $sitePermissionAlias = $adapter->createAlias();
            $qb->leftJoin('Omeka\Entity\Site.sitePermissions', $sitePermissionAlias);

            $expression = $qb->expr()->orX(
                $expression,
                // Users can view all sites they own.
                $qb->expr()->eq(
                    "Omeka\Entity\Site.owner",
                    $adapter->createNamedParameter($qb, $identity)
                ),
                // Users can view sites where they have a role (any role).
                $qb->expr()->eq(
                    "$sitePermissionAlias.user",
                    $adapter->createNamedParameter($qb, $identity)
                )
            );
        }
        $qb->andWhere($expression);
    }

    public function batchUpdatePostUser(ZendEvent $event)
    {
        $response = $event->getParam('response');
        $data = $response->getRequest()->getContent();
        if (!empty($data['remove_from_site_permission'])) {
            $siteIds = $data['remove_from_site_permission'];
            $collectionAction = 'remove';
            $role = null;
        } elseif (!empty($data['add_to_site_permission'])) {
            $siteIds = $data['add_to_site_permission'];
            $collectionAction = 'append';
            $role = empty($data['add_to_site_permission_role'])
                ? 'viewer'
                : $data['add_to_site_permission_role'];
        } else {
            return;
        }

        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        if (in_array(-1, $siteIds)) {
            $siteIds = $api->search('sites', [], ['responseContent' => 'resource'])->getContent();
        }
        if (empty($siteIds)) {
            return;
        }

        // The site adapter doesn't manage partial remove/append of users,
        // (collectionAction), so fetch all users first for each site directly.
        // Nevertheless, use the standard api next in order to trigger api
        // events of Site.
        // TODO Replace $fileData by $relatedData in ApiManager, so any related
        // entity will be able to be updated with the main entity.
        $userIds = array_intersect_key($response->getRequest()->getIds(), $response->getContent());
        foreach ($siteIds as $siteId) {
            $site = is_object($siteId)
                ? $siteId
                : $api->read('sites', $siteId, [], ['responseContent' => 'resource'])->getContent();
            $sitePermissions = $site->getSitePermissions();
            $newSitePermissions = [];
            switch ($collectionAction) {
                case 'remove':
                    if (empty($sitePermissions)) {
                        continue 2;
                    }
                    foreach ($sitePermissions as $sitePermission) {
                        $siteUserId = $sitePermission->getUser()->getId();
                        if (in_array($siteUserId, $userIds)) {
                            continue;
                        }
                        $newSitePermissions[] = [
                            'o:user' => ['o:id' => $siteUserId],
                            'o:role' => $sitePermission->getRole(),
                        ];
                    }
                    if (count($sitePermissions) == count($newSitePermissions)) {
                        continue 2;
                    }
                    break;
                case 'append':
                    foreach ($sitePermissions as $sitePermission) {
                        $siteUserId = $sitePermission->getUser()->getId();
                        $newSitePermissions[$siteUserId] = [
                            'o:user' => ['o:id' => $siteUserId],
                            'o:role' => $sitePermission->getRole(),
                        ];
                    }
                    foreach ($userIds as $userId) {
                        $newSitePermissions[$userId] = [
                            'o:user' => ['o:id' => $userId],
                            'o:role' => $role,
                        ];
                    }
                    break;
            }
            $api->update('sites',
                $site->getId(),
                ['o:site_permission' => $newSitePermissions],
                [],
                [
                    'isPartial' => true,
                    'collectionAction' => 'replace',
                    'flushEntityManager' => false,
                    'responseContent' => 'resource',
                    'finalize' => true,
                ]
            );
        }

        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');
        $entityManager->flush();
    }
}

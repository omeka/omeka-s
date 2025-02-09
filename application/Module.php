<?php
namespace Omeka;

use EasyRdf\Graph;
use Omeka\Api\Adapter\FulltextSearchableInterface;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Api\Representation\RepresentationInterface;
use Omeka\Entity\Item;
use Omeka\Entity\Media;
use Omeka\Module\AbstractModule;
use Laminas\EventManager\Event as ZendEvent;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Form\Element;
use Laminas\Json\Json;
use Laminas\View\Renderer\PhpRenderer;

/**
 * The Omeka module.
 */
class Module extends AbstractModule
{
    /**
     * This Omeka version.
     */
    const VERSION = '4.2.0-alpha';

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
            'Omeka\Api\Adapter\UserAdapter',
            'api.execute.post',
            [$this, 'batchUpdatePostUser']
        );

        $sharedEventManager->attach(
            'Laminas\View\Helper\Navigation\AbstractHelper',
            'isAllowed',
            [$this, 'navigationPageIsAllowed']
        );

        $sharedEventManager->attach(
            'Omeka\Entity\Media',
            'entity.remove.post',
            [$this, 'deleteMediaFiles']
        );

        $sharedEventManager->attach(
            'Omeka\Entity\ResourceTemplate',
            'entity.update.pre',
            [$this, 'refreshResourceTemplateResourceTitles']
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
            'api.create.post',
            [$this, 'saveFulltext']
        );

        $sharedEventManager->attach(
            '*',
            'api.update.post',
            [$this, 'saveFulltext']
        );

        $sharedEventManager->attach(
            'Omeka\Entity\Media',
            'entity.remove.pre',
            [$this, 'deleteFulltextMedia']
        );

        $sharedEventManager->attach(
            'Omeka\Api\Adapter\SitePageAdapter',
            'api.delete.pre',
            [$this, 'deleteFulltextPreSitePage']
        );

        $sharedEventManager->attach(
            '*',
            'api.delete.post',
            [$this, 'deleteFulltext']
        );

        $sharedEventManager->attach(
            '*',
            'api.search.query',
            [$this, 'searchFulltext']
        );
        $sharedEventManager->attach(
            '*',
            'api.search.query.finalize',
            [$this, 'searchFulltext']
        );

        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Media',
            'view.edit.form.advanced',
            [$this, 'addMediaAdvancedForm']
        );

        $sharedEventManager->attach(
            'Omeka\Controller\Site\Item',
            'view.show.after',
            [$this, 'noindexItem']
        );

        $sharedEventManager->attach(
            'Omeka\Controller\Site\Media',
            'view.show.after',
            [$this, 'noindexMedia']
        );

        $sharedEventManager->attach(
            'Omeka\Controller\Site\Item',
            'view.browse.after',
            [$this, 'noindexItemSet']
        );

        // Add favicon to layouts.
        $sharedEventManager->attach(
            '*',
            'view.layout',
            function (ZendEvent $event) {
                $view = $event->getTarget();
                // Get the favicon asset ID.
                if ($view->status()->isSiteRequest()) {
                    $faviconAssetId = $view->siteSetting('favicon');
                    if (!is_numeric($faviconAssetId)) {
                        $faviconAssetId = $view->setting('favicon');
                    }
                } else {
                    $faviconAssetId = $view->setting('favicon');
                }
                // Get the favicon href.
                if (is_numeric($faviconAssetId)) {
                    $faviconAsset = $view->api()->searchOne('assets', ['id' => $faviconAssetId])->getContent();
                    $href = $faviconAsset ? $faviconAsset->assetUrl() : null;
                } else {
                    $href = null; // Passing null clears the favicon.
                }
                $view->headLink(['rel' => 'icon', 'href' => $href], 'PREPEND');
            }
        );

        $sharedEventManager->attach(
            '*',
            'api.output.serialize',
            [$this, 'serializeApiOutputJsonLd']
        );

        $sharedEventManager->attach(
            '*',
            'api.output.serialize',
            [$this, 'serializeApiOutputRdf']
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
            $context[$row['prefix']] = $row['namespace_uri'];
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
     * Refresh resource titles when updating a resource template.
     *
     * @param ZendEvent $event
     */
    public function refreshResourceTemplateResourceTitles(ZendEvent $event)
    {
        $args = $event->getParam('LifecycleEventArgs');
        if (!$args->hasChangedField('titleProperty')) {
            return;
        }
        $services = $this->getServiceLocator();
        $resourceTemplate = $event->getTarget();
        $titleProperty = $resourceTemplate->getTitleProperty();
        if (!$titleProperty) {
            // Fall back on dcterms:title as the title property.
            $adapter = $services->get('Omeka\ApiAdapterManager')->get('items');
            $titleProperty = $adapter->getPropertyByTerm('dcterms:title');
        }
        $sql = '
        UPDATE resource
        SET resource.title = (
          SELECT value.value
          FROM value AS value
          WHERE value.resource_id = resource.id
          AND value.property_id = :property_id
          AND value.value IS NOT NULL
          AND value.value != ""
          ORDER BY value.id ASC
          LIMIT 1
        )
        WHERE resource.resource_template_id = :resource_template_id';
        $stmt = $services->get('Omeka\Connection')->prepare($sql);
        $stmt->bindValue('property_id', $titleProperty->getId());
        $stmt->bindValue('resource_template_id', $resourceTemplate->getId());
        $stmt->execute();
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
        $qb->innerJoin('omeka_root.item', $itemAlias);

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
        $expression = $qb->expr()->eq("omeka_root.isPublic", true);

        $identity = $this->getServiceLocator()
            ->get('Omeka\AuthenticationService')->getIdentity();
        if ($identity) {
            $sitePermissionAlias = $adapter->createAlias();
            $qb->leftJoin('omeka_root.sitePermissions', $sitePermissionAlias);

            $expression = $qb->expr()->orX(
                $expression,
                // Users can view all sites they own.
                $qb->expr()->eq(
                    'omeka_root.owner',
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

    /**
     * Save the fulltext of an API resource.
     *
     * @param ZendEvent $event
     */
    public function saveFulltext(ZendEvent $event)
    {
        $adapter = $event->getTarget();
        $entity = $event->getParam('response')->getContent();
        $fulltextSearch = $this->getServiceLocator()->get('Omeka\FulltextSearch');
        $fulltextSearch->save($entity, $adapter);

        // Item create needs special handling. We must save media fulltext here
        // because media is created via cascade persist (during item create/update),
        // which is invisible to normal API events.
        if ($entity instanceof Item) {
            $mediaAdapter = $adapter->getAdapter('media');
            foreach ($entity->getMedia() as $mediaEntity) {
                $fulltextSearch->save($mediaEntity, $mediaAdapter);
            }
        }
        // Item media needs special handling. We must update the item's fulltext
        // to append updated media data.
        if ($entity instanceof Media) {
            $itemEntity = $entity->getItem();
            $itemAdapter = $adapter->getAdapter('items');
            $fulltextSearch->save($itemEntity, $itemAdapter);
        }
    }

    /**
     * Delete the fulltext of a media.
     *
     * We must delete media fulltext here because media may be deleted via cascade
     * remove (during item update), which is invisible to normal API events.
     *
     * @param ZendEvent $event
     */
    public function deleteFulltextMedia(ZendEvent $event)
    {
        $fulltextSearch = $this->getServiceLocator()->get('Omeka\FulltextSearch');
        $adapterManager = $this->getServiceLocator()->get('Omeka\ApiAdapterManager');
        $mediaEntity = $event->getTarget();
        $mediaAdapter = $adapterManager->get('media');
        $fulltextSearch->delete($mediaEntity->getId(), $mediaAdapter);
    }

    /**
     * Prepare to delete the fulltext of a site page.
     *
     * The site_pages resource uses a compound ID that cannot be read from the
     * database. Here we set the actual entity ID to a request option so
     * self::deleteFulltext() can handle it correctly.
     *
     * @param ZendEvent $event
     */
    public function deleteFulltextPreSitePage(ZendEvent $event)
    {
        $request = $event->getParam('request');
        $conditions = $request->getId();
        if (!is_array($conditions)) {
            $conditions = ['id' => $conditions];
        }
        $em = $this->getServiceLocator()->get('Omeka\EntityManager');
        $sitePage = $em->getRepository('Omeka\Entity\SitePage')->findOneBy($conditions);
        $request->setOption('deleted_entity_id', $sitePage->getId());
    }

    /**
     * Delete the fulltext of an API resource.
     *
     * Typically this will delete on the resource ID that's set on the request
     * object. Resources that do not have conventional IDs should set the actual
     * ID to the "deleted_entity_id" request option prior to the api.delete.post
     * event. If the option exists, this function will use it to delete the
     * fulltext.
     *
     * @param ZendEvent $event
     */
    public function deleteFulltext(ZendEvent $event)
    {
        $adapter = $event->getTarget();
        $entity = $event->getParam('response')->getContent();
        $request = $event->getParam('request');
        $fulltextSearch = $this->getServiceLocator()->get('Omeka\FulltextSearch');

        // Media delete needs special handling. We must update the item's fulltext
        // to remove the appended media data. We return here because deleting media
        // fulltext is handled by self::deleteFulltextMedia().
        if ($entity instanceof Media) {
            $itemEntity = $entity->getItem();
            $itemAdapter = $adapter->getAdapter('items');
            $fulltextSearch->save($itemEntity, $itemAdapter);
            return;
        }

        // Note that the resource may not have an ID after being deleted. This
        // is why we must use $request->getId() rather than $entity->getId().
        $fulltextSearch->delete(
            $request->getOption('deleted_entity_id') ?? $request->getId(),
            $event->getTarget()
        );
    }

    /**
     * Search the fulltext of an API resource.
     *
     * Note that this only works for entity resources.
     *
     * @param ZendEvent $event
     */
    public function searchFulltext(ZendEvent $event)
    {
        $adapter = $event->getTarget();
        if (!($adapter instanceof FulltextSearchableInterface)) {
            return;
        }
        $query = $event->getParam('request')->getContent();
        if (!isset($query['fulltext_search']) || ('' === trim($query['fulltext_search']))) {
            return;
        }
        $qb = $event->getParam('queryBuilder');

        $match = 'MATCH(omeka_fulltext_search.title, omeka_fulltext_search.text) AGAINST (:omeka_fulltext_search)';

        if ('api.search.query' === $event->getName()) {

            // Join the fulltext search table and filter items. This must happen
            // during "api.search.query" because "api.search.query.finalize"
            // happens after we've already gotten the total count.

            $qb->setParameter('omeka_fulltext_search', $query['fulltext_search']);

            $joinConditions = sprintf(
                'omeka_fulltext_search.id = omeka_root.id AND omeka_fulltext_search.resource = %s',
                $adapter->createNamedParameter($qb, $adapter->getResourceName())
            );
            $qb->innerJoin('Omeka\Entity\FulltextSearch', 'omeka_fulltext_search', 'WITH', $joinConditions);

            // Filter out resources with no similarity.
            $qb->andWhere(sprintf('%s > 0', $match));

            // Set visibility constraints.
            $acl = $this->getServiceLocator()->get('Omeka\Acl');
            if ($acl->userIsAllowed('Omeka\Entity\Resource', 'view-all')) {
                // Users with the "view-all" privilege can view all resources.
                return;
            }
            // Users can view public resources they do not own.
            $constraints = $qb->expr()->eq('omeka_fulltext_search.isPublic', true);
            $identity = $this->getServiceLocator()->get('Omeka\AuthenticationService')->getIdentity();
            if ($identity) {
                // Users can view all resources they own.
                $constraints = $qb->expr()->orX(
                    $constraints,
                    $qb->expr()->eq('omeka_fulltext_search.owner', $identity->getId())
                );
            }
            $qb->andWhere($constraints);
        } elseif ('api.search.query.finalize' === $event->getName()) {

            // Order by relevance if this is a default sort. This must happen
            // during "api.search.query.finalize" because "api.search.query"
            // happens before we apply orderBys.

            if (isset($query['sort_by_default']) || !$qb->getDQLPart('orderBy')) {
                $sortOrder = 'asc' === $query['sort_order'] ? 'ASC' : 'DESC';
                if (isset($query['sort_order_default']) && isset($query['fulltext_search'])) {
                    // The default sort order for fulltext searches must be
                    // descending to account for the natural order of relevance
                    // scores.
                    $sortOrder = 'DESC';
                }
                $qb->orderBy($match, $sortOrder);
            }
        }
    }

    public function addMediaAdvancedForm(ZendEvent $event)
    {
        $view = $event->getTarget();
        $altTextInput = new Element\Textarea('o:alt_text');
        $altTextInput->setLabel('Alt text') // @translate
            ->setAttributes([
                'value' => $view->resource->altText() ?? null,
                'rows' => 4,
                'id' => 'alt_text',
            ]);
        $langInput = new Element\Text('o:lang');
        $langInput->setLabel('Language') // @translate
            ->setAttributes([
                'value' => $view->resource->lang() ?? null,
                'id' => 'lang',
                'class' => 'validate-language',
            ]);
        echo $view->formRow($altTextInput);
        echo $view->formRow($langInput);
    }

    public function noindexItem(ZendEvent $event)
    {
        $view = $event->getTarget();
        $this->noindexResourceShow($view, $view->item);
    }

    public function noindexMedia(ZendEvent $event)
    {
        $view = $event->getTarget();
        $this->noindexResourceShow($view, $view->media->item());
    }

    public function noindexItemSet(ZendEvent $event)
    {
        $view = $event->getTarget();
        if (!isset($view->itemSet)) {
            return;
        }
        $this->noindexResourceShow($view, $view->itemSet);
    }

    /**
     * Serialize the API output to JSON-LD.
     */
    public function serializeApiOutputJsonLd(ZendEvent $event)
    {
        $renderer = $event->getTarget();
        $model = $event->getParam('model');
        $format = $event->getParam('format');
        $payload = $event->getParam('payload');
        $output = $event->getParam('output');

        if ('jsonld' !== $format) {
            return;
        }

        $eventManager = $this->getServiceLocator()->get('EventManager');

        if ($payload instanceof RepresentationInterface) {
            $args = $eventManager->prepareArgs(['jsonLd' => $output]);
            $eventManager->trigger('rep.resource.json_output', $payload, $args);
            $output = $args['jsonLd'];
        }

        if (null !== $model->getOption('pretty_print')) {
            // Pretty print the JSON.
            $output = Json::prettyPrint($output);
        }

        $jsonpCallback = (string) $model->getOption('callback');
        if (!empty($jsonpCallback)) {
            // Wrap the JSON in a JSONP callback. Normally this would be done
            // via `$this->setJsonpCallback()` but we don't want to pass the
            // wrapped string to `rep.resource.json_output` handlers.
            $output = sprintf('%s(%s);', $jsonpCallback, $output);
            $renderer->setHasJsonpCallback(true);
        }

        $event->setParam('output', $output);
    }

    /**
     * Serialize the API output to RDF formats (rdfxml, n3, turtle, ntriples).
     */
    public function serializeApiOutputRdf(ZendEvent $event)
    {
        $renderer = $event->getTarget();
        $model = $event->getParam('model');
        $format = $event->getParam('format');
        $payload = $event->getParam('payload');
        $output = $event->getParam('output');

        if (!in_array($format, ['rdfxml', 'n3', 'turtle', 'ntriples'])) {
            return;
        }

        $eventManager = $this->getServiceLocator()->get('EventManager');

        $serializeRdf = function ($jsonLd) use ($format) {
            $graph = new Graph;
            $graph->parse(Json::encode($jsonLd), 'jsonld');
            return $graph->serialise($format);
        };

        $getJsonLdWithContext = function (RepresentationInterface $representation) use ($eventManager) {
            // Add the @context by encoding the output as JSON, then decoding to an array.
            static $context;
            if (!$context) {
                // Get the JSON-LD @context
                $args = $eventManager->prepareArgs(['context' => []]);
                $eventManager->trigger('api.context', null, $args);
                $context = $args['context'];
            }
            $jsonLd = Json::decode(Json::encode($representation), true);
            $jsonLd['@context'] = $context;
            return $jsonLd;
        };

        // Render a single representation (get).
        if ($payload instanceof RepresentationInterface) {
            $jsonLd = $getJsonLdWithContext($payload);
            $output = $serializeRdf($jsonLd);
        // Render multiple representations (getList);
        } elseif (is_array($payload) && array_filter($payload, fn ($object) => ($object instanceof RepresentationInterface))) {
            $jsonLd = [];
            foreach ($payload as $representation) {
                $jsonLd[] = $getJsonLdWithContext($representation);
            }
            $output = $serializeRdf($jsonLd);
        }

        $event->setParam('output', $output);
    }

    /**
     * Add a robots "noindex" metatag to the current view if the resource
     * being viewed does not belong to the current site.
     */
    protected function noindexResourceShow(PhpRenderer $view, AbstractResourceEntityRepresentation $resource)
    {
        $currentSite = $view->site;
        $sites = $resource->sites();
        if (!array_key_exists($currentSite->id(), $sites)) {
            $view->headMeta()->prependName('robots', 'noindex');
        }
    }
}

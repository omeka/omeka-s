<?php
namespace Mapping;

use Composer\Semver\Comparator;
use Doctrine\ORM\Events;
use Mapping\Db\Event\Listener\DetachOrphanMappings;
use Omeka\Api\Exception as ApiException;
use Omeka\Api\Request;
use Mapping\Entity\MappingFeature;
use Mapping\Form\Element\CopyCoordinates;
use Mapping\Form\Element\UpdateFeatures;
use Omeka\Module\AbstractModule;
use Omeka\Permissions\Acl;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\ModuleManager\ModuleManager;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceLocatorInterface;
use LongitudeOne\Spatial\PHP\Types\Geography;

class Module extends AbstractModule
{
    /**
     * Excludes providers that require API keys, access tokens, etc. Excludes
     * providers with limited bounds.
     */
    const BASEMAP_PROVIDERS = [
        'OpenStreetMap.Mapnik' => 'OpenStreetMap.Mapnik',
        'OpenStreetMap.DE' => 'OpenStreetMap.DE',
        'OpenStreetMap.France' => 'OpenStreetMap.France',
        'OpenStreetMap.HOT' => 'OpenStreetMap.HOT',
        'OpenTopoMap' => 'OpenTopoMap',
        'CyclOSM' => 'CyclOSM',
        'OpenMapSurfer.Roads' => 'OpenMapSurfer.Roads',
        'OpenMapSurfer.Hybrid' => 'OpenMapSurfer.Hybrid',
        'OpenMapSurfer.AdminBounds' => 'OpenMapSurfer.AdminBounds',
        'OpenMapSurfer.Hillshade' => 'OpenMapSurfer.Hillshade',
        'Esri.WorldStreetMap' => 'Esri.WorldStreetMap',
        'Esri.DeLorme' => 'Esri.DeLorme',
        'Esri.WorldTopoMap' => 'Esri.WorldTopoMap',
        'Esri.WorldImagery' => 'Esri.WorldImagery',
        'Esri.WorldTerrain' => 'Esri.WorldTerrain',
        'Esri.WorldShadedRelief' => 'Esri.WorldShadedRelief',
        'Esri.WorldPhysical' => 'Esri.WorldPhysical',
        'Esri.OceanBasemap' => 'Esri.OceanBasemap',
        'Esri.NatGeoWorldMap' => 'Esri.NatGeoWorldMap',
        'Esri.WorldGrayCanvas' => 'Esri.WorldGrayCanvas',
        'MtbMap' => 'MtbMap',
        'CartoDB.Positron' => 'CartoDB.Positron',
        'CartoDB.PositronNoLabels' => 'CartoDB.PositronNoLabels',
        'CartoDB.PositronOnlyLabels' => 'CartoDB.PositronOnlyLabels',
        'CartoDB.DarkMatter' => 'CartoDB.DarkMatter',
        'CartoDB.DarkMatterNoLabels' => 'CartoDB.DarkMatterNoLabels',
        'CartoDB.DarkMatterOnlyLabels' => 'CartoDB.DarkMatterOnlyLabels',
        'CartoDB.Voyager' => 'CartoDB.Voyager',
        'CartoDB.VoyagerNoLabels' => 'CartoDB.VoyagerNoLabels',
        'CartoDB.VoyagerOnlyLabels' => 'CartoDB.VoyagerOnlyLabels',
        'CartoDB.VoyagerLabelsUnder' => 'CartoDB.VoyagerLabelsUnder',
        'HikeBike.HikeBike' => 'HikeBike.HikeBike',
        'HikeBike.HillShading' => 'HikeBike.HillShading',
        'Wikimedia' => 'Wikimedia',
    ];

    public function init(ModuleManager $moduleManager)
    {
        require_once sprintf('%s/vendor/autoload.php', __DIR__);
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);

        // Set the corresponding visibility rules on Mapping resources.
        $em = $this->getServiceLocator()->get('Omeka\EntityManager');
        $filter = $em->getFilters()->getFilter('resource_visibility');
        $filter->addRelatedEntity('Mapping\Entity\Mapping', 'item_id');
        $filter->addRelatedEntity('Mapping\Entity\MappingFeature', 'item_id');

        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        $acl->allow(
            null,
            'Mapping\Controller\Site\Index'
        );
        $acl->allow(
            [Acl::ROLE_AUTHOR,
                Acl::ROLE_EDITOR,
                Acl::ROLE_GLOBAL_ADMIN,
                Acl::ROLE_REVIEWER,
                Acl::ROLE_SITE_ADMIN,
            ],
            ['Mapping\Api\Adapter\MappingFeatureAdapter',
             'Mapping\Api\Adapter\MappingAdapter',
             'Mapping\Entity\MappingFeature',
             'Mapping\Entity\Mapping',
            ]
        );

        $acl->allow(
            null,
            ['Mapping\Api\Adapter\MappingFeatureAdapter',
                'Mapping\Api\Adapter\MappingAdapter',
                'Mapping\Entity\MappingFeature',
                'Mapping\Entity\Mapping',
            ],
            ['show', 'browse', 'read', 'search']
            );

        $em = $this->getServiceLocator()->get('Omeka\EntityManager');
        $em->getEventManager()->addEventListener(
            Events::preFlush,
            new DetachOrphanMappings
        );
    }

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $conn = $serviceLocator->get('Omeka\Connection');
        $conn->exec("CREATE TABLE mapping_feature (id INT UNSIGNED AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, media_id INT DEFAULT NULL, `label` VARCHAR(255) DEFAULT NULL, geography GEOMETRY NOT NULL COMMENT '(DC2Type:geography)', INDEX IDX_34879C46126F525E (item_id), INDEX IDX_34879C46EA9FDD75 (media_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;");
        $conn->exec("CREATE TABLE mapping (id INT AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, bounds VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_49E62C8A126F525E (item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;");
        $conn->exec("ALTER TABLE mapping_feature ADD CONSTRAINT FK_34879C46126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE CASCADE;");
        $conn->exec("ALTER TABLE mapping_feature ADD CONSTRAINT FK_34879C46EA9FDD75 FOREIGN KEY (media_id) REFERENCES media (id) ON DELETE SET NULL;");
        $conn->exec("ALTER TABLE mapping ADD CONSTRAINT FK_49E62C8A126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE CASCADE;");
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $conn = $serviceLocator->get('Omeka\Connection');
        $conn->exec('DROP TABLE IF EXISTS mapping;');
        $conn->exec('DROP TABLE IF EXISTS mapping_feature');
    }

    public function upgrade($oldVersion, $newVersion, ServiceLocatorInterface $services)
    {
        if (Comparator::lessThan($oldVersion, '2.0.0-alpha')) {
            $this->upgradeToV2($services);
        }
        if (Comparator::lessThan($oldVersion, '2.0.0-alpha1')) {
            $conn = $services->get('Omeka\Connection');
            $conn->exec("UPDATE site_setting SET id = 'mapping_advanced_search_add_feature_presence' WHERE id = 'mapping_advanced_search_add_marker_presence'");
        }
    }

    /**
     * Upgrade to Mapping version 2.
     *
     * @param ServiceLocatorInterface $services
     */
    public function upgradeToV2(ServiceLocatorInterface $services)
    {
        $conn = $services->get('Omeka\Connection');

        $wrap = function ($num, $min, $max) {
            $d = $max - $min;
            return ($num === $max) ? $num : fmod(fmod($num - $min, $d) + $d, $d) + $min;
        };

        // Create the mapping_feature table.
        $conn->exec("CREATE TABLE mapping_feature (id INT UNSIGNED AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, media_id INT DEFAULT NULL, `label` VARCHAR(255) DEFAULT NULL, geography GEOMETRY NOT NULL COMMENT '(DC2Type:geography)', INDEX IDX_34879C46126F525E (item_id), INDEX IDX_34879C46EA9FDD75 (media_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;");
        $conn->exec("ALTER TABLE mapping_feature ADD CONSTRAINT FK_34879C46126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE CASCADE;");
        $conn->exec("ALTER TABLE mapping_feature ADD CONSTRAINT FK_34879C46EA9FDD75 FOREIGN KEY (media_id) REFERENCES media (id) ON DELETE SET NULL;");

        // Prepare the insert statement.
        $insertSql = 'INSERT INTO mapping_feature (id, item_id, media_id, `label`, geography) VALUES (:id, :item_id, :media_id, :label, ST_PointFromText(:point))';
        $insertStmt = $conn->prepare($insertSql);

        // Iterate all rows in mapping_marker, converting longitudes and
        // latitudes to point geometries.
        $markers = $conn->iterateAssociative('SELECT * FROM mapping_marker');
        foreach ($markers as $marker) {
            if (!(is_numeric($marker['lng']) && is_numeric($marker['lat']))) {
                // Invalid coordinates. Longitude and latitude must be numeric.
                continue;
            }
            // Wrap longitudes and latitudes that are outside their valid ranges
            // into their valid geographical equivalents.
            $longitude = $wrap($marker['lng'], -180.0, 180.0);
            $latitude = $wrap($marker['lat'], -90.0, 90.0);

            // Bind values and insert the row.
            $insertStmt->bindValue('id', $marker['id']);
            $insertStmt->bindValue('item_id', $marker['item_id']);
            $insertStmt->bindValue('media_id', $marker['media_id']);
            $insertStmt->bindValue('label', $marker['label']);
            $insertStmt->bindValue('point', sprintf('POINT(%s %s)', $longitude, $latitude));
            $insertStmt->executeQuery();
        }

        // Drop the mapping_marker table now that we're done with it.
        $conn->executeStatement('DROP TABLE mapping_marker;');
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        // Add the map tab to admin pages.
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.add.section_nav',
            [$this, 'addMapTab']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.edit.section_nav',
            [$this, 'addMapTab']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.show.section_nav',
            [$this, 'addMapTab']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\ItemSet',
            'view.show.section_nav',
            [$this, 'addMapTab']
        );
        // Add the map form to admin pages.
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.add.form.after',
            [$this, 'addItemForm']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.edit.form.after',
            [$this, 'addItemForm']
        );
        // Add the map to admin pages.
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.show.after',
            [$this, 'addResourceMap']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\ItemSet',
            'view.show.after',
            [$this, 'addResourceMap']
        );
        // Add the mapping fields to advanced search pages.
        $sharedEventManager->attach(
            'Mapping\Controller\Site\Index',
            'view.advanced_search',
            [$this, 'filterMapBrowseAdvancedSearch']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.advanced_search',
            [$this, 'filterItemAdvancedSearch']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Site\Item',
            'view.advanced_search',
            [$this, 'filterItemAdvancedSearch']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Query',
            'view.advanced_search',
            [$this, 'filterItemAdvancedSearch']
        );
        $sharedEventManager->attach(
            '*',
            'view.search.filters',
            [$this, 'filterSearchFilters']
         );
        // Add the "has_features" filter to item search.
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.search.query',
            [$this, 'handleApiSearchQuery']
        );
        // Add the Mapping term definition.
        $sharedEventManager->attach(
            '*',
            'api.context',
            [$this, 'filterApiContext']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Representation\ItemRepresentation',
            'rep.resource.json',
            [$this, 'filterItemJsonLd']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.hydrate.post',
            [$this, 'handleMapping']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.hydrate.post',
            [$this, 'handleFeatures']
        );
        $sharedEventManager->attach(
            'Omeka\Form\SiteSettingsForm',
            'form.add_elements',
            [$this, 'addSiteSettings']
        );
        $sharedEventManager->attach(
            'Omeka\Form\SiteSettingsForm',
            'form.add_input_filters',
            [$this, 'addSiteSettingsInputFilters']
        );
        $sharedEventManager->attach(
            'Omeka\Form\ResourceBatchUpdateForm',
            'form.add_elements',
            function (Event $event) {
                $form = $event->getTarget();
                if ('item' !== $form->getOption('resource_type')) {
                    return; // Include elements only on item batch edit.
                }

                $groups = $form->getOption('element_groups');
                $groups['mapping'] = 'Mapping'; // @translate
                $form->setOption('element_groups', $groups);

                $form->add([
                    'type' => 'checkbox',
                    'name' => 'mapping_delete_features',
                    'options' => [
                        'element_group' => 'mapping',
                        'label' => 'Delete features', // @translate
                    ],
                    'attributes' => [
                        'data-collection-action' => 'replace',
                    ],
                ]);
                $form->add([
                    'type' => CopyCoordinates::class,
                    'name' => 'mapping_copy_coordinates',
                    'options' => [
                        'element_group' => 'mapping',
                        'label' => 'Copy coordinates to markers', // @translate
                    ],
                ]);
                $form->add([
                    'type' => UpdateFeatures::class,
                    'name' => 'mapping_update_features',
                    'options' => [
                        'element_group' => 'mapping',
                        'label' => 'Update features', // @translate
                    ],
                ]);
            }
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.preprocess_batch_update',
            function (Event $event) {
                $data = $event->getParam('data');
                $rawData = $event->getParam('request')->getContent();
                if (isset($rawData['mapping_delete_features'])) {
                    $data['mapping_delete_features'] = $rawData['mapping_delete_features'];
                }
                if ($this->copyCoordinatesDataIsValid($rawData)) {
                    $data['mapping_copy_coordinates'] = $rawData['mapping_copy_coordinates'];
                }
                if ($this->updateFeaturesDataIsValid($rawData)) {
                    $data['mapping_update_features'] = $rawData['mapping_update_features'];
                }
                $event->setParam('data', $data);
            }
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.update.post',
            [$this, 'deleteFeatures'],
            30
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.update.post',
            [$this, 'copyCoordinates'],
            20
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.update.post',
            [$this, 'updateFeatures'],
            10
        );
        // Copy Mapping-related data for the CopyResources module.
        $sharedEventManager->attach(
            '*',
            'copy_resources.sites.post',
            function (Event $event) {
                $copyResources = $event->getParam('copy_resources');
                $siteCopy = $event->getParam('resource_copy');

                $copyResources->revertSiteBlockLayouts($siteCopy->id(), 'mappingMap');
                $copyResources->revertSiteBlockLayouts($siteCopy->id(), 'mappingMapQuery');
                $copyResources->revertSiteNavigationLinkTypes($siteCopy->id(), 'mapping');
            }
        );
        $sharedEventManager->attach(
            '*',
            'copy_resources.items.pre',
            function (Event $event) {
                $jsonLd = $event->getParam('json_ld');
                unset($jsonLd['o-module-mapping:mapping']);
                unset($jsonLd['o-module-mapping:feature']);
                $event->setParam('json_ld', $jsonLd);
            }
        );
        $sharedEventManager->attach(
            '*',
            'copy_resources.items.post',
            function (Event $event) {
                $services = $this->getServiceLocator();
                $api = $services->get('Omeka\ApiManager');
                $connection = $services->get('Omeka\Connection');

                $item = $event->getParam('resource');
                $itemCopy = $event->getParam('resource_copy');
                $copyResources = $event->getParam('copy_resources');

                $mappings = $api->search('mappings', ['item_id' => $item->id()])->getContent();
                $features = $api->search('mapping_features', ['item_id' => $item->id()])->getContent();

                foreach ($mappings as $mapping) {
                    $callback = function (&$jsonLd) use ($itemCopy) {
                        $jsonLd['o:item']['o:id'] = $itemCopy->id();
                    };
                    $copyResources->createResourceCopy('mappings', $mapping, $callback);
                }
                foreach ($features as $feature) {
                    $callback = function (&$jsonLd) use ($itemCopy) {
                        $jsonLd['o:item']['o:id'] = $itemCopy->id();
                    };
                    $copyResources->createResourceCopy('mapping_features', $feature, $callback);
                }
            }
        );
    }

    public function addSiteSettings(Event $event)
    {
        $services = $this->getServiceLocator();
        $siteSettings = $services->get('Omeka\Settings\Site');
        $form = $event->getTarget();

        $groups = $form->getOption('element_groups');
        $groups['mapping'] = 'Mapping'; // @translate
        $form->setOption('element_groups', $groups);

        $form->add([
            'type' => 'checkbox',
            'name' => 'mapping_advanced_search_add_feature_presence',
            'options' => [
                'element_group' => 'mapping',
                'label' => 'Add feature presence to advanced search', // @translate
            ],
            'attributes' => [
                'value' => $siteSettings->get('mapping_advanced_search_add_feature_presence'),
            ],
        ]);
        $form->add([
            'type' => 'checkbox',
            'name' => 'mapping_advanced_search_add_geographic_location',
            'options' => [
                'element_group' => 'mapping',
                'label' => 'Add geographic location to advanced search', // @translate
            ],
            'attributes' => [
                'value' => $siteSettings->get('mapping_advanced_search_add_geographic_location'),
            ],
        ]);
        $form->add([
            'type' => 'checkbox',
            'name' => 'mapping_disable_clustering',
            'options' => [
                'element_group' => 'mapping',
                'label' => 'Disable clustering of map features', // @translate
                'info' => 'Map features are markers, polygons, polylines, and rectangles. Note that large features may not cluster.',  // @translate
            ],
            'attributes' => [
                'value' => $siteSettings->get('mapping_disable_clustering'),
            ],
        ]);
        $form->add([
            'type' => 'select',
            'name' => 'mapping_basemap_provider',
            'options' => [
                'element_group' => 'mapping',
                'label' => 'Basemap provider', // @translate
                'empty_option' => '[Default provider]', // @translate
                'value_options' => self::BASEMAP_PROVIDERS,
            ],
            'attributes' => [
                'value' => $siteSettings->get('mapping_basemap_provider'),
            ],
        ]);
        $form->add([
            'type' => 'number',
            'name' => 'mapping_browse_per_page',
            'options' => [
                'element_group' => 'mapping',
                'label' => 'Map browse items per page', // @translate
                'info' => 'Set the maximum number of items that have features to display per page on the map browse page. Limit to a reasonable amount to avoid reaching the server memory limit and to improve client performance.', // @translate
                'placeholder' => '5000',
            ],
            'attributes' => [
                'value' => $siteSettings->get('mapping_browse_per_page', '5000'),
            ],
        ]);
    }

    public function addSiteSettingsInputFilters(Event $event)
    {
        $inputFilter = $event->getParam('inputFilter');
        $inputFilter->add([
            'name' => 'mapping_basemap_provider',
            'allow_empty' => true,
        ]);
    }

    public function addMapTab(Event $event)
    {
        $view = $event->getTarget();
        if ('view.show.section_nav' === $event->getName()) {
            // Don't render the mapping tab if there is no mapping data.
            $resource = $event->getParam('resource');
            $hasMapping = false;
            $hasFeatures = false;
            switch (get_class($resource)) {
                case 'Omeka\Api\Representation\ItemRepresentation':
                    $hasMapping = $view->api()->searchOne('mappings', ['item_id' => $resource->id()])->getTotalResults();
                    $hasFeatures = $view->api()->search('mapping_features', ['item_id' => $resource->id(), 'limit' => 0])->getTotalResults();
                    break;
                case 'Omeka\Api\Representation\ItemSetRepresentation':
                    $hasFeatures = $view->api()->search('mapping_features', ['item_set_id' => $resource->id(), 'limit' => 0])->getTotalResults();
                    break;
                default:
                    return;
            }
            if (!($hasMapping || $hasFeatures)) {
                return;
            }
        }
        $sectionNav = $event->getParam('section_nav');
        $sectionNav['mapping-section'] = $view->translate('Mapping');
        $event->setParam('section_nav', $sectionNav);
    }

    public function addItemForm(Event $event)
    {
        echo $event->getTarget()->partial('common/mapping-item-form');
    }

    public function addResourceMap(Event $event)
    {
        echo $event->getTarget()->partial('common/mapping-resource-map');
    }

    public function filterMapBrowseAdvancedSearch(Event $event)
    {
        $partials = $event->getParam('partials');
        // Remove any unneeded partials.
        $removePartials = ['common/advanced-search/sort'];
        $partials = array_diff($partials, $removePartials);
        // Put geographic location fields at the beginning of the form.
        array_unshift($partials, 'common/advanced-search/mapping-item-geographic-location');
        $event->setParam('partials', $partials);
    }

    public function filterItemAdvancedSearch(Event $event)
    {
        $services = $this->getServiceLocator();
        $status = $services->get('Omeka\Status');
        $siteSettings = $services->get('Omeka\Settings\Site');
        $partials = $event->getParam('partials');

        // Conditionally add the feature presence field.
        if ($status->isAdminRequest() || ($status->isSiteRequest() && $siteSettings->get('mapping_advanced_search_add_feature_presence'))) {
            $partials[] = 'common/advanced-search/mapping-item-feature-presence';
        }
        // Conditionally add the geographic location fields.
        if ($status->isAdminRequest() || ($status->isSiteRequest() && $siteSettings->get('mapping_advanced_search_add_geographic_location'))) {
            $partials[] = 'common/advanced-search/mapping-item-geographic-location';
        }
        $event->setParam('partials', $partials);
    }

    public function filterSearchFilters(Event $event)
    {
        $view = $event->getTarget();
        $query = $event->getParam('query');
        $filters = $event->getParam('filters');

        // Add the feature presence search filter label.
        if (isset($query['has_features']) && in_array($query['has_features'], ['0', '1'])) {
            $filterLabel = $view->translate('Map feature presence');
            $filters[$filterLabel][] = $query['has_features'] ? $view->translate('Has map features') : $view->translate('Has no map features');
        }
        // Add the geographic location search filter label.
        $address = $query['mapping_address'] ?? null;
        $radius = $query['mapping_radius'] ?? null;
        $radiusUnit = $query['mapping_radius_unit'] ?? null;
        if (isset($address) && '' !== trim($address) && isset($radius) && is_numeric($radius)) {
            $filterLabel = $view->translate('Geographic location');
            $filters[$filterLabel][] = sprintf('%s (%s %s)', $address, $radius, $radiusUnit);
        }
        $event->setParam('filters', $filters);
    }

    public function handleApiSearchQuery(Event $event)
    {
        $itemAdapter = $event->getTarget();
        $qb = $event->getParam('queryBuilder');
        $query = $event->getParam('request')->getContent();
        if (isset($query['has_features']) && (is_numeric($query['has_features']) || is_bool($query['has_features']))) {
            $mappingFeatureAlias = $itemAdapter->createAlias();
            if ($query['has_features']) {
                $qb->innerJoin(
                    'Mapping\Entity\MappingFeature', $mappingFeatureAlias,
                    'WITH', "$mappingFeatureAlias.item = omeka_root.id"
                );
            } else {
                $qb->leftJoin(
                    'Mapping\Entity\MappingFeature', $mappingFeatureAlias,
                    'WITH', "$mappingFeatureAlias.item = omeka_root.id"
                );
                $qb->andWhere($qb->expr()->isNull($mappingFeatureAlias));
            }
        }
        $address = $query['mapping_address'] ?? null;
        $radius = $query['mapping_radius'] ?? null;
        $radiusUnit = $query['mapping_radius_unit'] ?? null;
        if (isset($address) && '' !== trim($address) && isset($radius) && is_numeric($radius)) {
            $mappingFeatureAdapter = $itemAdapter->getAdapter('mapping_features');
            $mappingFeatureAdapter->buildGeographicLocationQuery($qb, $address, $radius, $radiusUnit, $itemAdapter);
        }
    }

    public function filterApiContext(Event $event)
    {
        $context = $event->getParam('context');
        $context['o-module-mapping'] = 'http://omeka.org/s/vocabs/module/mapping#';
        $event->setParam('context', $context);
    }

    /**
     * Add the mapping and feature data to the item JSON-LD.
     *
     * Event $event
     */
    public function filterItemJsonLd(Event $event)
    {
        $item = $event->getTarget();
        $jsonLd = $event->getParam('jsonLd');
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        // Add mapping data.
        $response = $api->search('mappings', ['item_id' => $item->id()]);
        foreach ($response->getContent() as $mapping) {
            // There's zero or one mapping per item.
            $jsonLd['o-module-mapping:mapping'] = $mapping;
        }
        // Add feature data.
        $response = $api->search('mapping_features', ['item_id' => $item->id()]);
        foreach ($response->getContent() as $feature) {
            // There's zero or more features per item.
            $jsonLd['o-module-mapping:feature'][] = $feature;
        }

        $event->setParam('jsonLd', $jsonLd);
    }

    /**
     * Handle hydration for mapping data.
     *
     * @param Event $event
     */
    public function handleMapping(Event $event)
    {
        $itemAdapter = $event->getTarget();
        $request = $event->getParam('request');
        $item = $event->getParam('entity');

        if (!$itemAdapter->shouldHydrate($request, 'o-module-mapping:mapping')) {
            return;
        }

        $mappingsAdapter = $itemAdapter->getAdapter('mappings');
        $mappingData = $request->getValue('o-module-mapping:mapping', []);

        $bounds = null;

        if (isset($mappingData['o-module-mapping:bounds'])
            && '' !== trim($mappingData['o-module-mapping:bounds'])
        ) {
            $bounds = $mappingData['o-module-mapping:bounds'];
        }

        $mapping = null;
        if (Request::CREATE !== $request->getOperation()) {
            try {
                $mapping = $mappingsAdapter->findEntity(['item' => $item]);
            } catch (ApiException\NotFoundException $e) {
                // no action
            }
        }

        if (null === $bounds) {
            // This request has no mapping data. If a mapping for this item
            // exists, delete it. If no mapping for this item exists, do nothing.
            if ($mapping) {
                $subRequest = new \Omeka\Api\Request('delete', 'mappings');
                $subRequest->setId($mapping->getId());
                $mappingsAdapter->deleteEntity($subRequest);
            }
            return;
        }

        // This request has mapping data. If a mapping for this item exists,
        // update it. If no mapping for this item exists, create it.
        if ($mapping) {
            // Update mapping
            $subRequest = new \Omeka\Api\Request('update', 'mappings');
            $subRequest->setId($mappingData['o:id']);
            $subRequest->setContent($mappingData);
            $mappingsAdapter->hydrateEntity($subRequest, $mapping, new \Omeka\Stdlib\ErrorStore);
        } else {
            // Create mapping
            $subRequest = new \Omeka\Api\Request('create', 'mappings');
            $subRequest->setContent($mappingData);
            $mapping = new \Mapping\Entity\Mapping;
            $mapping->setItem($event->getParam('entity'));
            $mappingsAdapter->hydrateEntity($subRequest, $mapping, new \Omeka\Stdlib\ErrorStore);
            $mappingsAdapter->getEntityManager()->persist($mapping);
        }
    }

    /**
     * Handle hydration for feature data.
     *
     * @param Event $event
     */
    public function handleFeatures(Event $event)
    {
        $itemAdapter = $event->getTarget();
        $request = $event->getParam('request');

        if (!$itemAdapter->shouldHydrate($request, 'o-module-mapping:feature')) {
            return;
        }

        $item = $event->getParam('entity');
        $entityManager = $itemAdapter->getEntityManager();
        $featuresAdapter = $itemAdapter->getAdapter('mapping_features');
        $retainFeatureIds = [];

        $existingFeatures = [];
        if ($item->getId()) {
            $dql = 'SELECT mf FROM Mapping\Entity\MappingFeature mf INDEX BY mf.id WHERE mf.item = ?1';
            $query = $entityManager->createQuery($dql)->setParameter(1, $item->getId());
            $existingFeatures = $query->getResult();
        }

        // Create/update features passed in the request.
        foreach ($request->getValue('o-module-mapping:feature', []) as $featureData) {
            if (isset($featureData['o:id'])) {
                if (!isset($existingFeatures[$featureData['o:id']])) {
                    // This feature belongs to another item. Ignore it.
                    continue;
                }
                $subRequest = new \Omeka\Api\Request('update', 'mapping_features');
                $subRequest->setId($featureData['o:id']);
                $subRequest->setContent($featureData);
                $feature = $featuresAdapter->findEntity($featureData['o:id'], $subRequest);
                $featuresAdapter->hydrateEntity($subRequest, $feature, new \Omeka\Stdlib\ErrorStore);
                $retainFeatureIds[] = $feature->getId();
            } else {
                $subRequest = new \Omeka\Api\Request('create', 'mapping_features');
                $subRequest->setContent($featureData);
                $feature = new \Mapping\Entity\MappingFeature;
                $feature->setItem($item);
                $featuresAdapter->hydrateEntity($subRequest, $feature, new \Omeka\Stdlib\ErrorStore);
                $entityManager->persist($feature);
            }
        }

        // Delete existing features not passed in the request.
        foreach ($existingFeatures as $existingFeatureId => $existingFeature) {
            if (!in_array($existingFeatureId, $retainFeatureIds)) {
                $entityManager->remove($existingFeature);
            }
        }
    }

    /**
     * Does the passed data contain valid copy-coordinates data?
     *
     * @param array $data
     * return bool
     */
    public function copyCoordinatesDataIsValid(array $data)
    {
        $coordinatesData = $data['mapping_copy_coordinates'] ?? null;
        if (!is_array($coordinatesData)) {
            return false;
        }
        if (!(isset($coordinatesData['copy_action']) && in_array($coordinatesData['copy_action'], ['by_item_property', 'by_item_properties', 'by_media_property', 'by_media_properties']))) {
            return false;
        }
        if (in_array($coordinatesData['copy_action'], ['by_item_property', 'by_media_property'])) {
            if (!(isset($coordinatesData['property']) && is_numeric($coordinatesData['property']))) {
                return false;
            }
            if (!(isset($coordinatesData['order']) && in_array($coordinatesData['order'], ['latlng', 'lnglat']))) {
                return false;
            }
            if (!(isset($coordinatesData['delimiter']) && in_array($coordinatesData['delimiter'], [',', ' ', '/', ':']))) {
                return false;
            }
        } elseif (in_array($coordinatesData['copy_action'], ['by_item_properties', 'by_media_properties'])) {
            if (!(isset($coordinatesData['property_lat']) && is_numeric($coordinatesData['property_lat']) && isset($coordinatesData['property_lng']) && is_numeric($coordinatesData['property_lng']))) {
                return false;
            }
        }
        if (isset($coordinatesData['assign_media']) && !in_array($coordinatesData['assign_media'], ['1', '0'])) {
            return false;
        }
        return true;
    }

    /**
     * Does the passed data contain valid update-features data?
     *
     * @param array $data
     * return bool
     */
    public function updateFeaturesDataIsValid(array $data)
    {
        $featuresData = $data['mapping_update_features'] ?? null;
        if (!is_array($featuresData)) {
            return false;
        }
        if (isset($featuresData['label_property_source']) && !in_array($featuresData['label_property_source'], ['item', 'primary_media', 'assigned_media'])) {
            return false;
        }
        if (isset($featuresData['image']) && !in_array($featuresData['image'], ['', 'unassign', 'primary_media'])) {
            return false;
        }
        return true;
    }

    /**
     * Delete features.
     *
     * @param Event $event
     */
    public function deleteFeatures(Event $event)
    {
        $data = $event->getParam('request')->getContent();
        $item = $event->getParam('response')->getContent();

        if (!(isset($data['mapping_delete_features']) && $data['mapping_delete_features'])) {
            return;
        }

        $services = $this->getServiceLocator();
        $entityManager = $services->get('Omeka\EntityManager');

        $dql = 'DELETE FROM Mapping\Entity\MappingFeature m WHERE m.item = :item_id';
        $entityManager->createQuery($dql)
            ->setParameter('item_id', $item->getId())
            ->execute();
    }

    /**
     * Copy coordinates from property values to mapping features.
     *
     * @param Event $event
     */
    public function copyCoordinates(Event $event)
    {
        $data = $event->getParam('request')->getContent();
        $item = $event->getParam('response')->getContent();

        if (!$this->copyCoordinatesDataIsValid($data)) {
            return;
        }

        $data = $data['mapping_copy_coordinates'];

        $copyAction = $data['copy_action'];
        $propertyId = $data['property'] ?? null;
        $propertyLatId = $data['property_lat'] ?? null;
        $propertyLngId = $data['property_lng'] ?? null;
        $order = $data['order'] ?? null;
        $delimiter = $data['delimiter'] ?? null;
        $assignMedia = $data['assign_media'] ?? null;

        $services = $this->getServiceLocator();
        $entityManager = $services->get('Omeka\EntityManager');

        $dqlValues = 'SELECT v
            FROM Omeka\Entity\Value v
            WHERE v.resource = :resource_id
            AND v.property = :property_id
            AND v.value IS NOT NULL';
        $dqlMedia = 'SELECT m
            FROM Omeka\Entity\Media m
            WHERE m.item = :item_id';
        $dqlFeature = "SELECT f
            FROM Mapping\Entity\MappingFeature f
            WHERE f.item = :item_id
            AND ST_GeometryType(f.geography) = 'POINT'
            AND ST_Intersects(ST_Buffer(ST_GeomFromText(:buffer_center_point), 0.00001), f.geography) = 1";

        $allCoordinates = [];
        // By one item property containing both latitude and longitude
        if ('by_item_property' === $copyAction) {
            $values = $entityManager->createQuery($dqlValues)
                ->setParameter('resource_id', $item->getId())
                ->setParameter('property_id', $propertyId)
                ->getResult();
            if (!$values) {
                return; // Relevant values don't exist. Do nothing.
            }
            foreach ($values as $value) {
                $allCoordinates[] = $value->getValue();
            }
            // By two item properties, one latitude and the other longitude
        } elseif ('by_item_properties' === $copyAction) {
            $latValues = $entityManager->createQuery($dqlValues)
                ->setParameter('resource_id', $item->getId())
                ->setParameter('property_id', $propertyLatId)
                ->getResult();
            $lngValues = $entityManager->createQuery($dqlValues)
                ->setParameter('resource_id', $item->getId())
                ->setParameter('property_id', $propertyLngId)
                ->getResult();
            if (!($latValues && $lngValues)) {
                return; // Relevant values don't exist. Do nothing.
            }
            if (count($latValues) !== count($lngValues)) {
                return; // Missing latitudes or longitudes. Do nothing.
            }
            foreach ($latValues as $index => $latValue) {
                $lat = $latValue->getValue();
                $lng = $lngValues[$index]->getValue();
                $allCoordinates[] = sprintf('%s,%s', $lat, $lng);
            }
            $delimiter = ',';
        // By one media property containing both latitude and longitude
        } elseif ('by_media_property' === $copyAction) {
            $medias = $entityManager->createQuery($dqlMedia)
                ->setParameter('item_id', $item->getId())
                ->getResult();
            foreach ($medias as $media) {
                $values = $entityManager->createQuery($dqlValues)
                    ->setParameter('resource_id', $media->getId())
                    ->setParameter('property_id', $propertyId)
                    ->getResult();
                if (!$values) {
                    continue; // Relevant values don't exist. Do nothing.
                }
                foreach ($values as $value) {
                    $allCoordinates[$media->getId()] = $value->getValue();
                }
            }
            // By two media properties, one latitude and the other longitude
        } elseif ('by_media_properties' === $copyAction) {
            $medias = $entityManager->createQuery($dqlMedia)
                ->setParameter('item_id', $item->getId())
                ->getResult();
            foreach ($medias as $media) {
                $latValues = $entityManager->createQuery($dqlValues)
                    ->setParameter('resource_id', $media->getId())
                    ->setParameter('property_id', $propertyLatId)
                    ->getResult();
                $lngValues = $entityManager->createQuery($dqlValues)
                    ->setParameter('resource_id', $media->getId())
                    ->setParameter('property_id', $propertyLngId)
                    ->getResult();
                if (!($latValues && $lngValues)) {
                    continue; // Relevant values don't exist. Do nothing.
                }
                if (count($latValues) !== count($lngValues)) {
                    continue; // Missing latitudes or longitudes. Do nothing.
                }
                foreach ($latValues as $index => $latValue) {
                    $lat = $latValue->getValue();
                    $lng = $lngValues[$index]->getValue();
                    $allCoordinates[$media->getId()] = sprintf('%s,%s', $lat, $lng);
                }
                $delimiter = ',';
            }
        }

        // @see: https://stackoverflow.com/a/31408260
        $latRegex = '^(\+|-)?(?:90(?:(?:\.0{1,6})?)|(?:[0-9]|[1-8][0-9])(?:(?:\.[0-9]+)?))$';
        $lngRegex = '^(\+|-)?(?:180(?:(?:\.0{1,6})?)|(?:[0-9]|[1-9][0-9]|1[0-7][0-9])(?:(?:\.[0-9]+)?))$';
        foreach ($allCoordinates as $key => $coordinates) {
            $coordinates = explode($delimiter, $coordinates);
            if (2 !== count($coordinates)) {
                continue; // Coordinates must have latitude and longitude. Skip.
            }
            $coordinates = array_map('trim', $coordinates);
            $lat = ('latlng' === $order) ? $coordinates[0] : $coordinates[1];
            $lng = ('lnglat' === $order) ? $coordinates[0] : $coordinates[1];
            if (!preg_match(sprintf('/%s/', $latRegex), $lat)) {
                continue; // Invalid latitude. Skip.
            }
            if (!preg_match(sprintf('/%s/', $lngRegex), $lng)) {
                continue; // Invalid longitude. Skip.
            }

            // Handle duplicate coordinates. For this purpose, duplicates are
            // those with a difference of less than 0.00001. That's a difference
            // of 0° 00′ 0.036″ DMS, or 1.11 m. At this precision, markers are
            // near indistinguishable on the map on maximum zoom.
            // @see https://en.wikipedia.org/wiki/Decimal_degrees#Precision
            $feature = $entityManager->createQuery($dqlFeature)
                ->setParameter('item_id', $item->getId())
                ->setParameter('buffer_center_point', sprintf('POINT(%s %s)', $lng, $lat))
                ->setMaxResults(1)
                ->getOneOrNullResult();
            if ($feature) {
                // This feature already exists.
            } else {
                // This feature does not exist. Create it.
                $point = new Geography\Point($lng, $lat);
                $feature = new MappingFeature;
                $feature->setItem($item);
                $feature->setGeography($point);
                $entityManager->persist($feature);
            }
            // Assign media to feature if directed to do so.
            if (in_array($copyAction, ['by_media_property', 'by_media_properties']) && $assignMedia) {
                $media = $entityManager->find('Omeka\Entity\Media', $key);
                $feature->setMedia($media);
            }
        }
        $entityManager->flush();
    }

    /**
     * Update features.
     *
     * @param Event $event
     */
    public function updateFeatures(Event $event)
    {
        $data = $event->getParam('request')->getContent();
        $item = $event->getParam('response')->getContent();

        if (!$this->updateFeaturesDataIsValid($data)) {
            return;
        }

        $data = $data['mapping_update_features'];

        $labelPropertyId = $data['label_property'] ?? null;
        $labelPropertySource = $data['label_property_source'] ?? null;
        $image = $data['image'] ?? null;

        $services = $this->getServiceLocator();
        $entityManager = $services->get('Omeka\EntityManager');

        $dql = 'SELECT m
            FROM Mapping\Entity\MappingFeature m
            WHERE m.item = :item_id';
        $features = $entityManager->createQuery($dql)
            ->setParameter('item_id', $item->getId())
            ->getResult();
        if (!$features) {
            return; // Features don't exist. Do nothing.
        }
        foreach ($features as $feature) {
            // Handle feature image.
            if ('primary_media' === $image) {
                $feature->setMedia($this->getPrimaryMedia($item));
            } elseif ('unassign' === $image) {
                $feature->setMedia(null);
            }
            // Handle maker label.
            if ('-1' === $labelPropertyId) {
                $feature->setLabel(null);
            } elseif (is_numeric($labelPropertyId)) {
                $resourceId = null;
                if ('primary_media' === $labelPropertySource) {
                    $media = $this->getPrimaryMedia($item);
                    if ($media) {
                        $resourceId = $media->getId();
                    }
                } elseif ('assigned_media' === $labelPropertySource) {
                    $media = $feature->getMedia();
                    if ($media) {
                        $resourceId = $media->getId();
                    }
                } else {
                    $resourceId = $item->getId();
                }
                if ($resourceId) {
                    $dql = 'SELECT v
                        FROM Omeka\Entity\Value v
                        WHERE v.resource = :resource_id
                        AND v.property = :property_id
                        AND v.value IS NOT NULL
                        ORDER BY v.id ASC';
                    $value = $entityManager->createQuery($dql)
                        ->setParameter('resource_id', $resourceId)
                        ->setParameter('property_id', $labelPropertyId)
                        ->setMaxResults(1)
                        ->getOneOrNullResult();
                    if ($value) {
                        $feature->setLabel(mb_substr($value->getValue(), 0, 255));
                    }
                }
            }
            $entityManager->persist($feature);
        }
        $entityManager->flush();
    }

    public function getPrimaryMedia(\Omeka\Entity\Item $item)
    {
        $services = $this->getServiceLocator();
        $entityManager = $services->get('Omeka\EntityManager');
        // Prioritize the item's primary media, if any.
        $media = $item->getPrimaryMedia();
        if (!$media) {
            // Fall back on the item's first media, if any.
            $dql = 'SELECT m
                FROM Omeka\Entity\Media m
                WHERE m.item = :item
                ORDER BY m.position ASC';
            $media = $entityManager->createQuery($dql)
                ->setParameter('item', $item)
                ->setMaxResults(1)
                ->getOneOrNullResult();
        }
        return $media;
    }
}

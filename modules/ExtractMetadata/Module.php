<?php
namespace ExtractMetadata;

use DateTime;
use ExtractMetadata\Entity\ExtractMetadata;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Form\Element;
use Laminas\ModuleManager\ModuleManager;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Entity;
use Omeka\File\Store\Local;
use Omeka\Module\AbstractModule;

class Module extends AbstractModule
{
    const ACTIONS = [
        'refresh' => 'Refresh metadata', // @translate
        'refresh_map' => 'Refresh and map metadata', // @translate
        'map' => 'Map metadata', // @translate
        'delete' => 'Delete metadata', // @translate
    ];

    public function init(ModuleManager $moduleManager)
    {
        require_once sprintf('%s/vendor/autoload.php', __DIR__);
    }

    public function getConfig()
    {
        return include sprintf('%s/config/module.config.php', __DIR__);
    }

    public function install(ServiceLocatorInterface $services)
    {
        $sql = <<<'SQL'
CREATE TABLE extract_metadata (id INT UNSIGNED AUTO_INCREMENT NOT NULL, media_id INT NOT NULL, extracted DATETIME NOT NULL, extractor VARCHAR(255) NOT NULL, metadata LONGTEXT NOT NULL COMMENT '(DC2Type:json)', INDEX IDX_4DA36818EA9FDD75 (media_id), UNIQUE INDEX media_extractor (media_id, extractor), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
ALTER TABLE extract_metadata ADD CONSTRAINT FK_4DA36818EA9FDD75 FOREIGN KEY (media_id) REFERENCES media (id) ON DELETE CASCADE;
SQL;
        $conn = $services->get('Omeka\Connection');
        $conn->exec('SET FOREIGN_KEY_CHECKS=0;');
        $conn->exec($sql);
        $conn->exec('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function uninstall(ServiceLocatorInterface $services)
    {
        // Drop extract_metadata table.
        $conn = $services->get('Omeka\Connection');
        $conn->exec('SET FOREIGN_KEY_CHECKS=0;');
        $conn->exec('DROP TABLE IF EXISTS extract_metadata;');
        $conn->exec('SET FOREIGN_KEY_CHECKS=1;');
        // Delete extract_metadata settings.
        $settings = $services->get('Omeka\Settings');
        $settings->delete('extract_metadata_enabled_extractors');
        $settings->delete('extract_metadata_enabled_mapper');
        $settings->delete('extract_metadata_json_pointer_crosswalk');
    }

    public function getConfigForm(PhpRenderer $view)
    {
        $services = $this->getServiceLocator();
        $extractors = $services->get('ExtractMetadata\ExtractorManager');
        $mappers = $services->get('ExtractMetadata\MapperManager');
        $settings = $services->get('Omeka\Settings');
        return $view->partial('common/extract-metadata-config-form', [
            'extractors' => $extractors,
            'mappers' => $mappers,
            'enabledExtractors' => $settings->get('extract_metadata_enabled_extractors', []),
            'enabledMapper' => $settings->get('extract_metadata_enabled_mapper', null),
            'jsonPointerCrosswalk' => $settings->get('extract_metadata_json_pointer_crosswalk', []),
        ]);
    }

    public function handleConfigForm(AbstractController $controller)
    {
        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        $formData = $controller->params()->fromPost();
        // Validate the form data.
        $enabledExtractors = $formData['enabled_extractors'] ?? [];
        if (!is_array($enabledExtractors)) {
            $enabledExtractors = [];
        }
        $enabledMapper = $formData['enabled_mapper'] ?? null;
        if (!is_string($enabledMapper)) {
            $enabledMapper = null;
        }
        $jsonPointerCrosswalk = $formData['json_pointer_crosswalk'] ?? [];
        if (!is_array($jsonPointerCrosswalk)) {
            $jsonPointerCrosswalk = [];
        }
        // Must reset array keys or jQuery's $.each() will pass incorrect data.
        $jsonPointerCrosswalk = array_values($jsonPointerCrosswalk);
        $settings->set('extract_metadata_enabled_extractors', $enabledExtractors);
        $settings->set('extract_metadata_enabled_mapper', $enabledMapper);
        $settings->set('extract_metadata_json_pointer_crosswalk', $jsonPointerCrosswalk);
        return true;
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        /*
         * Extract metadata before ingesting media file. This will only happen
         * when creating the media.
         */
        $sharedEventManager->attach(
            '*',
            'media.ingest_file.pre',
            function (Event $event) {
                $mediaEntity = $event->getTarget();
                $tempFile = $event->getParam('tempFile');
                $metadataEntities = $this->extractMetadata(
                    $tempFile->getTempPath(),
                    $tempFile->getMediaType(),
                    $mediaEntity
                );
                $this->mapMetadata($mediaEntity, $metadataEntities);
            }
        );
        /*
         * After hydrating a media, perform the requested extract_metadata_action.
         * This will only happen when updating the media.
         */
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\MediaAdapter',
            'api.hydrate.post',
            function (Event $event) {
                $request = $event->getParam('request');
                if ('update' !== $request->getOperation()) {
                    // This is not an update operation.
                    return;
                }
                $mediaEntity = $event->getParam('entity');
                $data = $request->getContent();
                $action = $data['extract_metadata_action'] ?? 'default';
                $this->performAction($mediaEntity, $action);
            }
        );
        /*
         * After hydrating an item, perform the requested extract_metadata_action.
         * This will only happen when updating the item.
         */
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.hydrate.post',
            function (Event $event) {
                $request = $event->getParam('request');
                if ('update' !== $request->getOperation()) {
                    // This is not an update operation.
                    return;
                }
                $item = $event->getParam('entity');
                $data = $request->getContent();
                $action = $data['extract_metadata_action'] ?? 'default';
                foreach ($item->getMedia() as $mediaEntity) {
                    $this->performAction($mediaEntity, $action);
                }
            }
        );
        /*
         * Add the ExtractMetadata control to the media/item batch update forms.
         */
        $sharedEventManager->attach(
            'Omeka\Form\ResourceBatchUpdateForm',
            'form.add_elements',
            function (Event $event) {
                $form = $event->getTarget();
                $resourceType = $form->getOption('resource_type');
                if (!in_array($resourceType, ['media', 'item'])) {
                    // This is not a media or item batch update form.
                    return;
                }
                $element = $this->getActionSelect();
                $element->setAttribute('data-collection-action', 'replace');
                $form->add($element);
            }
        );
        /*
         * Don't require the ExtractMetadata control in the batch update forms.
         */
        $sharedEventManager->attach(
            'Omeka\Form\ResourceBatchUpdateForm',
            'form.add_input_filters',
            function (Event $event) {
                $form = $event->getTarget();
                $resourceType = $form->getOption('resource_type');
                if (!in_array($resourceType, ['media', 'item'])) {
                    // This is not a media or item batch update form.
                    return;
                }
                $inputFilter = $event->getParam('inputFilter');
                $inputFilter->add([
                    'name' => 'extract_metadata_action',
                    'required' => false,
                ]);
            }
        );
        /*
         * Authorize the "extract_metadata_action" key when preprocessing the
         * batch update data. This will signal the process to refresh or map the
         * metadata while updating each resource in the batch.
         */
        $preprocessBatchUpdate = function (Event $event) {
            $adapter = $event->getTarget();
            $data = $event->getParam('data');
            $rawData = $event->getParam('request')->getContent();
            if (isset($rawData['extract_metadata_action'])
                && in_array($rawData['extract_metadata_action'], array_keys(self::ACTIONS))
            ) {
                $data['extract_metadata_action'] = $rawData['extract_metadata_action'];
            }
            $event->setParam('data', $data);
        };
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\MediaAdapter',
            'api.preprocess_batch_update',
            $preprocessBatchUpdate
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.preprocess_batch_update',
            $preprocessBatchUpdate
        );
        /*
         * Add an "Extract metadata" tab to the item edit page.
         */
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.edit.section_nav',
            function (Event $event) {
                $view = $event->getTarget();
                if (!$view->item->media()) {
                    // The item must have media to render tab.
                    return;
                }
                $view->headLink()->appendStylesheet($view->assetUrl(
                    'css/admin/extract-metadata.css',
                    'ExtractMetadata'
                ));
                $sectionNavs = $event->getParam('section_nav');
                $sectionNavs['extract-metadata'] = $view->translate('Extract metadata');
                $event->setParam('section_nav', $sectionNavs);
            }
        );
        /*
         * Add an "Extract metadata" tab to the media edit page.
         */
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Media',
            'view.edit.section_nav',
            function (Event $event) {
                $view = $event->getTarget();
                $view->headLink()->appendStylesheet($view->assetUrl(
                    'css/admin/extract-metadata.css',
                    'ExtractMetadata'
                ));
                $sectionNavs = $event->getParam('section_nav');
                $sectionNavs['extract-metadata'] = $view->translate('Extract metadata');
                $event->setParam('section_nav', $sectionNavs);
            }
        );
        /*
         * Add an "Extract metadata" tab to the media show page.
         */
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Media',
            'view.show.section_nav',
            function (Event $event) {
                $view = $event->getTarget();
                $metadataEntities = $this->getServiceLocator()
                    ->get('Omeka\EntityManager')
                    ->getRepository(ExtractMetadata::class)
                    ->findBy(['media' => $view->media->id()]);
                if (!$metadataEntities) {
                    // The media must have metadata to render tab.
                    return;
                }
                $view->headLink()->appendStylesheet($view->assetUrl(
                    'css/admin/extract-metadata.css',
                    'ExtractMetadata'
                ));
                $sectionNavs = $event->getParam('section_nav');
                $sectionNavs['extract-metadata'] = $view->translate('Extract metadata');
                $event->setParam('section_nav', $sectionNavs);
            }
        );
        /*
         * Add an "Extract metadata" section to the item edit page.
         */
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.edit.form.after',
            function (Event $event) {
                $view = $event->getTarget();
                if (!$view->item->media()) {
                    // The item must have media to render section.
                    return;
                }
                $view->headLink()->appendStylesheet($view->assetUrl(
                    'css/admin/extract-metadata.css',
                    'ExtractMetadata'
                ));
                $element = $this->getActionSelect();
                echo $view->partial('common/extract-metadata-section-item-edit', [
                    'element' => $element,
                ]);
            }
        );
        /*
         * Add an "Extract metadata" section to the media edit page.
         */
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Media',
            'view.edit.form.after',
            function (Event $event) {
                $view = $event->getTarget();
                $view->headLink()->appendStylesheet($view->assetUrl(
                    'css/admin/extract-metadata.css',
                    'ExtractMetadata')
                );
                $element = $this->getActionSelect();
                $metadataEntities = $this->getServiceLocator()
                    ->get('Omeka\EntityManager')
                    ->getRepository(ExtractMetadata::class)
                    ->findBy(['media' => $view->media->id()]);
                echo $view->partial('common/extract-metadata-section-media-edit', [
                    'element' => $element,
                    'metadataEntities' => $metadataEntities,
                ]);
            }
        );
        /*
         * Add an "Extract metadata" section to the media show page.
         */
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Media',
            'view.show.after',
            function (Event $event) {
                $view = $event->getTarget();
                $view->headLink()->appendStylesheet($view->assetUrl(
                    'css/admin/extract-metadata.css',
                    'ExtractMetadata'
                ));
                $metadataEntities = $this->getServiceLocator()
                    ->get('Omeka\EntityManager')
                    ->getRepository(ExtractMetadata::class)
                    ->findBy(['media' => $view->media->id()]);
                if (!$metadataEntities) {
                    // The media must have metadata to render section.
                    return;
                }
                echo $view->partial('common/extract-metadata-section-media-show', [
                    'metadataEntities' => $metadataEntities,
                ]);
            }
        );
        /*
         * IIIF Presentation module
         *
         * Provide accurate metadata for IIIF content resources.
         */
        $sharedEventManager->attach(
            '*',
            'iiif_presentation.2.media.canvas',
            [$this, 'iiifPresentationContentResource']
        );
        $sharedEventManager->attach(
            '*',
            'iiif_presentation.3.media.canvas',
            [$this, 'iiifPresentationContentResource']
        );
    }

    /**
     * Extract metadata.
     *
     * @param string $filePath
     * @param string $mediaType
     * @param Entity\Media $mediaEntity
     * @return array An array of metadata entities
     */
    public function extractMetadata($filePath, $mediaType, Entity\Media $mediaEntity)
    {
        if (!@is_file($filePath)) {
            // The file doesn't exist.
            return [];
        }
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');
        $extractors = $services->get('ExtractMetadata\ExtractorManager');
        $entityManager = $services->get('Omeka\EntityManager');
        $enabledExtractors = $settings->get('extract_metadata_enabled_extractors', []);
        $metadataEntities = [];
        foreach ($extractors->getRegisteredNames() as $extractorName) {
            if (!in_array($extractorName, $enabledExtractors)) {
                // The extractor is not enabled.
                continue;
            }
            $extractor = $extractors->get($extractorName);
            if (!$extractor->isAvailable()) {
                // The extractor is unavailable.
                continue;
            }
            if (!$extractor->supports($mediaType)) {
                // The extractor does not support this media type.
                continue;
            }
            $metadata = $extractor->extract($filePath, $mediaType);
            if (!is_array($metadata)) {
                // The extractor did not return an array.
                continue;
            }
            // Avoid JSON and character encoding errors by encoding the metadata
            // into JSON and back into an array, ignoring invalid UTF-8. Invalid
            // JSON would break on the Doctrine level, so we include this here.
            $metadata = json_decode(json_encode($metadata, JSON_INVALID_UTF8_IGNORE), true);
            if (!$metadata) {
                // Could not convert array to JSON.
                continue;
            }
            // Create the metadata entity.
            $metadataEntity = $entityManager->getRepository(ExtractMetadata::class)
                ->findOneBy([
                    'media' => $mediaEntity,
                    'extractor' => $extractorName,
                ]);
            if (!$metadataEntity) {
                $metadataEntity = new ExtractMetadata;
                $metadataEntity->setMedia($mediaEntity);
                $metadataEntity->setExtractor($extractorName);
                $entityManager->persist($metadataEntity);
            }
            $metadataEntity->setExtracted(new DateTime('now'));
            $metadataEntity->setMetadata($metadata);
            $metadataEntities[] = $metadataEntity;
        }
        return $metadataEntities;
    }

    /**
     * Map metadata.
     *
     * @param Entity\Media $mediaEntity
     * @param array $metadataEntities
     */
    public function mapMetadata(Entity\Media $mediaEntity, array $metadataEntities)
    {
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');
        $mappers = $services->get('ExtractMetadata\MapperManager');
        $enabledMapper = $settings->get('extract_metadata_enabled_mapper', null);
        try {
            $mapper = $mappers->get($enabledMapper);
        } catch (ServiceNotFoundException $e) {
            // The mapper is not registered.
            return;
        }
        $mapper->map($mediaEntity, $metadataEntities);
    }

    /**
     * Delete metadata.
     *
     * @param Entity\Media $mediaEntity
     */
    public function deleteMetadata(Entity\Media $mediaEntity)
    {
        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');
        $metadataEntities = $this->getMetadataEntities($mediaEntity);
        foreach ($metadataEntities as $metadataEntity) {
            $entityManager->remove($metadataEntity);
        }
    }

    /**
     * Perform an extract metadata action.
     *
     * @param Entity\Media $mediaEntity
     * @param string $action
     */
    public function performAction(Entity\Media $mediaEntity, $action)
    {
        if (!in_array($action, array_keys(self::ACTIONS))) {
            // This is an invalid action.
            return;
        }
        switch ($action) {
            case 'refresh':
                // Files must be stored locally to refresh extracted metadata.
                $store = $this->getServiceLocator()->get('Omeka\File\Store');
                if ($store instanceof Local) {
                    $filePath = $store->getLocalPath(sprintf('original/%s', $mediaEntity->getFilename()));
                    $this->extractMetadata($filePath, $mediaEntity->getMediaType(), $mediaEntity);
                }
                break;
            case 'refresh_map':
                // Files must be stored locally to refresh extracted metadata.
                $store = $this->getServiceLocator()->get('Omeka\File\Store');
                if ($store instanceof Local) {
                    $filePath = $store->getLocalPath(sprintf('original/%s', $mediaEntity->getFilename()));
                    $metadataEntities = $this->extractMetadata($filePath, $mediaEntity->getMediaType(), $mediaEntity);
                    $this->mapMetadata($mediaEntity, $metadataEntities);
                }
                break;
            case 'map':
                $metadataEntities = $this->getMetadataEntities($mediaEntity);
                $this->mapMetadata($mediaEntity, $metadataEntities);
                break;
            case 'delete':
                $this->deleteMetadata($mediaEntity);
                break;
        }
    }

    /**
     * Get action select element.
     *
     * @return Element\Select
     */
    public function getActionSelect()
    {
        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        $store = $this->getServiceLocator()->get('Omeka\File\Store');
        $mapperEnabled = $settings->get('extract_metadata_enabled_mapper');
        $valueOptions = [];
        if ($store instanceof Local) {
            // Files must be stored locally to refresh extracted metadata.
            $valueOptions['refresh'] = self::ACTIONS['refresh'];
            if ($mapperEnabled) {
                // A mapper must be enabled to map metadata.
                $valueOptions['refresh_map'] = self::ACTIONS['refresh_map'];
            }
        }
        if ($mapperEnabled) {
            // A mapper must be enabled to map metadata.
            $valueOptions['map'] = self::ACTIONS['map'];
        }
        $valueOptions['delete'] = self::ACTIONS['delete'];
        $element = new Element\Select('extract_metadata_action');
        $element->setLabel('Extract metadata');
        $element->setEmptyOption('[No action]'); // @translate
        $element->setValueOptions($valueOptions);
        return $element;
    }

    /**
     * Get all metadata entities for a media.
     *
     * @param Entity\Media $mediaEntity
     * @return array
     */
    public function getMetadataEntities(Entity\Media $mediaEntity)
    {
        return $this->getServiceLocator()
            ->get('Omeka\EntityManager')
            ->getRepository(ExtractMetadata::class)
            ->findBy(['media' => $mediaEntity]);
    }

    /**
     * Get a metadata entity for a media/extractor.
     *
     * @param Entity\Media $mediaEntity
     * @param string $extractor
     * @return ExtractMetadata
     */
    public function getMetadataEntity(Entity\Media $mediaEntity, $extractor)
    {
        return $this->getServiceLocator()
            ->get('Omeka\EntityManager')
            ->getRepository(ExtractMetadata::class)
            ->findOneBy([
                'media' => $mediaEntity,
                'extractor' => $extractor,
            ]);
    }

    /**
     * IIIF Presentation module:
     *
     * Provide accurate metadata for IIIF content resources.
     *
     * @param Event $event
     */
    public function iiifPresentationContentResource(Event $event)
    {
        $eventName = $event->getName();
        $canvas = $event->getParam('canvas');
        $mediaId = $event->getParam('media_id');
        try {
            $media = $this->getServiceLocator()
                ->get('Omeka\EntityManager')
                ->find('Omeka\Entity\Media', $mediaId);
        } catch (\Exception $e) {
            // The media does not exist.
            return;
        }
        $width = $this->iiifPresentationWidth($media);
        $height = $this->iiifPresentationHeight($media);
        $duration = $this->iiifPresentationDuration($media);
        switch ($eventName) {
            // IIIF Presentation API v2
            case 'iiif_presentation.2.media.canvas':
                if ($width && $height) {
                    // Content resource must have both width and height.
                    $canvas['images'][0]['resource']['width'] = $width;
                    $canvas['images'][0]['resource']['height'] = $height;
                }
                break;
            // IIIF Presentation API v3
            case 'iiif_presentation.3.media.canvas':
                if ($width && $height) {
                    // Content resource must have both width and height.
                    $canvas['items'][0]['items'][0]['body']['width'] = $width;
                    $canvas['items'][0]['items'][0]['body']['height'] = $height;
                }
                if ($duration) {
                    $canvas['items'][0]['items'][0]['body']['duration'] = $duration;
                }
                break;
            default:
                return;
        }
        $event->setParam('canvas', $canvas);
    }

    /**
     * Get the width of the content resource.
     *
     * @param Entity\Media $mediaEntity
     * @return ?int
     */
    public function iiifPresentationWidth(Entity\Media $mediaEntity)
    {
        $metadataEntity = $this->getMetadataEntity($mediaEntity, 'exiftool');
        if ($metadataEntity) {
            $metadata = $metadataEntity->getMetadata();
            // First, try the Composite entry.
            $dimensions = @$metadata['Composite']['ImageSize'];
            if ($dimensions) {
                [$width, $height] = explode('x', $dimensions);
                return $width;
            }
            // Then, iteralte through each entry to find ImageWidth.
            foreach ($metadata as $data) {
                if (isset($data['ImageWidth'])) {
                    return $data['ImageWidth'];
                }
            }
        }
    }

    /**
     * Get the height of the content resource.
     *
     * @param Entity\Media $mediaEntity
     * @return ?int
     */
    public function iiifPresentationHeight(Entity\Media $mediaEntity)
    {
        $metadataEntity = $this->getMetadataEntity($mediaEntity, 'exiftool');
        if ($metadataEntity) {
            $metadata = $metadataEntity->getMetadata();
            // First, try the Composite entry.
            $dimensions = @$metadata['Composite']['ImageSize'];
            if ($dimensions) {
                [$width, $height] = explode('x', $dimensions);
                return $height;
            }
            // Then, iteralte through each entry to find ImageHeight.
            foreach ($metadata as $data) {
                if (isset($data['ImageHeight'])) {
                    return $data['ImageHeight'];
                }
            }
        }
    }

    /**
     * Get the duration of the content resource.
     *
     * @param Entity\Media $mediaEntity
     * @return ?float
     */
    public function iiifPresentationDuration(Entity\Media $mediaEntity)
    {
        $metadataEntity = $this->getMetadataEntity($mediaEntity, 'exiftool');
        if ($metadataEntity) {
            $metadata = $metadataEntity->getMetadata();
            // First, try the Composite entry.
            $duration = @$metadata['Composite']['Duration'];
            if ($duration) {
                preg_match('/[\d]{1,2}:[\d]{2}:[\d]{2}/', $duration, $matches);
                return strtotime($matches[0]) - strtotime('00:00:00');
            }
            // Then, iteralte through each entry to find Duration.
            foreach ($metadata as $data) {
                if (isset($data['Duration'])) {
                    preg_match('/[\d]{1,2}:[\d]{2}:[\d]{2}/', $data['Duration'], $matches);
                    return strtotime($matches[0]) - strtotime('00:00:00');
                }
            }
        }
    }
}

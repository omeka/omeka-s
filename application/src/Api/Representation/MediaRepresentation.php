<?php
namespace Omeka\Api\Representation;

class MediaRepresentation extends AbstractResourceEntityRepresentation
{
    /**
     * {@inheritDoc}
     */
    public function getControllerName()
    {
        return 'media';
    }

    /**
     * {@inheritDoc}
     */
    public function getResourceJsonLdType()
    {
        return 'o:Media';
    }

    /**
     * {@inheritDoc}
     */
    public function getResourceJsonLd()
    {
        return [
            'o:ingester' => $this->ingester(),
            'o:renderer' => $this->renderer(),
            'o:item' => $this->item()->getReference(),
            'o:source' => $this->source(),
            'o:media_type' => $this->mediaType(),
            'o:sha256' => $this->sha256(),
            'o:size' => is_numeric($this->size()) ? (int) $this->size() : null,
            'o:filename' => $this->filename(),
            'o:lang' => $this->lang(),
            'o:original_url' => $this->originalUrl(),
            'o:thumbnail_urls' => $this->thumbnailUrls(),
            'data' => $this->mediaData(),
        ];
    }

    /**
     * Return the HTML necessary to render this media.
     *
     * @return string
     */
    public function render(array $options = [])
    {
        return $this->getViewHelper('media')->render($this, $options);
    }

    /**
     * Get the URL to the original file.
     *
     * @return string
     */
    public function originalUrl()
    {
        if (!$this->hasOriginal()) {
            return null;
        }
        return $this->getFileUrl('original', $this->filename());
    }

    /**
     * Get the URL to a thumbnail image.
     *
     * @param string $type The type of thumbnail
     * @return string
     */
    public function thumbnailUrl($type)
    {
        $thumbnailManager = $this->getServiceLocator()->get('Omeka\File\ThumbnailManager');

        if (!$this->hasThumbnails() || !$thumbnailManager->typeExists($type)) {
            $fallbacks = $thumbnailManager->getFallbacks();
            $mediaType = $this->mediaType();
            $topLevelType = strstr($mediaType, '/', true);

            if (isset($fallbacks[$mediaType])) {
                // Prioritize a match against the full media type, e.g. "image/jpeg"
                $fallback = $fallbacks[$mediaType];
            } elseif ($topLevelType && isset($fallbacks[$topLevelType])) {
                // Then fall back on a match against the top-level type, e.g. "image"
                $fallback = $fallbacks[$topLevelType];
            } else {
                $fallback = $thumbnailManager->getDefaultFallback();
            }

            $assetUrl = $this->getServiceLocator()->get('ViewHelperManager')->get('assetUrl');
            return $assetUrl($fallback[0], $fallback[1], true);
        }
        return $this->getFileUrl($type, $this->storageId(), 'jpg');
    }

    /**
     * Get all thumbnail URLs, keyed by type.
     *
     * @return array
     */
    public function thumbnailUrls()
    {
        if (!$this->hasThumbnails()) {
            return [];
        }
        $thumbnailManager = $this->getServiceLocator()->get('Omeka\File\ThumbnailManager');
        $urls = [];
        foreach ($thumbnailManager->getTypes() as $type) {
            $urls[$type] = $this->thumbnailUrl($type);
        }
        return $urls;
    }

    /**
     * Get the media ingester
     *
     * @return string
     */
    public function ingester()
    {
        return $this->resource->getIngester();
    }

    /**
     * Get the ingester's label
     *
     * @return string
     */
    public function ingesterLabel()
    {
        $ingester = $this->getServiceLocator()
            ->get('Omeka\Media\Ingester\Manager')
            ->get($this->ingester());
        return $ingester->getLabel();
    }

    /**
     * Get the media renderer
     *
     * @return string
     */
    public function renderer()
    {
        return $this->resource->getRenderer();
    }

    /**
     * Get the media data.
     *
     * Named getMediaData() so as not to override parent::getData().
     *
     * @return mixed
     */
    public function mediaData()
    {
        return $this->resource->getData();
    }

    /**
     * Get the source of the media.
     *
     * @return string|null
     */
    public function source()
    {
        return $this->resource->getSource();
    }

    /**
     * Get the Internet media type of the media.
     *
     * @return string|null
     */
    public function mediaType()
    {
        return $this->resource->getMediaType();
    }

    /**
     * Get the SHA-256 of the media.
     *
     * @return string|null
     */
    public function sha256()
    {
        return $this->resource->getSha256();
    }

    /**
     * Get the size of the media file.
     *
     * @return int
     */
    public function size()
    {
        return $this->resource->getSize();
    }

    /**
     * Get the media's filename (if any).
     *
     * @return string|null
     */
    public function filename()
    {
        return $this->resource->getFilename();
    }

    /**
     * Get the media's storage ID (if any).
     *
     * @return string|null
     */
    public function storageId()
    {
        return $this->resource->getStorageId();
    }

    /**
     * Get the media's file extension (if any).
     *
     * @return string|null
     */
    public function extension()
    {
        return $this->resource->getExtension();
    }

    /**
     * Check whether this media has an original file.
     *
     * @return bool
     */
    public function hasOriginal()
    {
        return $this->resource->hasOriginal();
    }

    /**
     * Check whether this media has thumbnail images.
     *
     * @return bool
     */
    public function hasThumbnails()
    {
        return $this->resource->hasThumbnails();
    }

    /**
     * Get the language code of the media.
     *
     * @return string|null
     */
    public function lang()
    {
        return $this->resource->getLang();
    }

    /**
     * Return the parent item parent of this media.
     *
     * @return ItemRepresentation
     */
    public function item()
    {
        return $this->getAdapter('items')
            ->getRepresentation($this->resource->getItem());
    }

    /**
     * Get the display title for this resource.
     *
     * Change the fallback title to be the media's source, if it exists.
     *
     * @param string|null $default
     * @return string|null
     */
    public function displayTitle($default = null)
    {
        $source = $this->source();
        if (!$source) {
            $source = $default;
        }

        return parent::displayTitle($source);
    }

    public function siteUrl($siteSlug = null, $canonical = false)
    {
        if (!$siteSlug) {
            $siteSlug = $this->getServiceLocator()->get('Application')
                ->getMvcEvent()->getRouteMatch()->getParam('site-slug');
        }
        $url = $this->getViewHelper('Url');
        return $url(
            'site/resource-id',
            [
                'site-slug' => $siteSlug,
                'controller' => 'media',
                'id' => $this->id(),
            ],
            ['force_canonical' => $canonical]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function primaryMedia()
    {
        return $this;
    }
}

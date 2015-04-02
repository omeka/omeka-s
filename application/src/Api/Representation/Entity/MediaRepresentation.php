<?php
namespace Omeka\Api\Representation\Entity;

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
    public function getResourceJsonLd()
    {
        return array(
            'o:type' => $this->type(),
            'o:data' => $this->mediaData(),
            'o:item' => $this->getReference(
                null,
                $this->getData()->getItem(),
                $this->getAdapter('items')
            ),
            'o:source'     => $this->source(),
            'o:media_type' => $this->mediaType(),
            'o:filename'   => $this->filename(),
            'o:original_url' => $this->originalUrl(),
            'o:thumbnail_urls' => $this->thumbnailUrls()
        );
    }

    /**
     * Return the HTML necessary to render this media.
     *
     * @return string
     */
    public function render(array $options = array())
    {
        $mediaHelper = $this->getAdapter()
            ->getServiceLocator()
            ->get('ViewHelperManager')
            ->get('Media');
        return $mediaHelper->render($this, $options);
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
        $fileManager = $this->getServiceLocator()->get('Omeka\File\Manager');
        return $fileManager->getOriginalUrl($this->getData());
    }

    /**
     * Get the URL to a thumbnail image.
     *
     * @param string $type The type of thumbnail
     * @return string
     */
    public function thumbnailUrl($type)
    {
        if (!$this->hasThumbnails()) {
            return null;
        }
        $fileManager = $this->getServiceLocator()->get('Omeka\File\Manager');
        if (!$fileManager->thumbnailTypeExists($type)) {
            return null;
        }
        return $fileManager->getThumbnailUrl($type, $this->getData());
    }

    /**
     * Get all thumbnail URLs, keyed by type.
     *
     * @return array
     */
    public function thumbnailUrls()
    {
        if (!$this->hasThumbnails()) {
            return array();
        }
        $fileManager = $this->getServiceLocator()->get('Omeka\File\Manager');
        return $fileManager->getThumbnailUrls($this->getData());
    }

    /**
     * Get the media type
     *
     * @return string
     */
    public function type()
    {
        return $this->getData()->getType();
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
        return $this->getData()->getData();
    }

    /**
     * Get the source of the media.
     *
     * @return string|null
     */
    public function source()
    {
        return $this->getData()->getSource();
    }

    /**
     * Get the Internet media type of the media.
     *
     * @return string|null
     */
    public function mediaType()
    {
        return $this->getData()->getMediaType();
    }

    /**
     * Get the media's filename (if any).
     *
     * @return string|null
     */
    public function filename()
    {
        return $this->getData()->getFilename();
    }

    /**
     * Check whether this media has an original file.
     *
     * @return bool
     */
    public function hasOriginal()
    {
        return $this->getData()->hasOriginal();
    }

    /**
     * Check whether this media has thumbnail images.
     *
     * @return bool
     */
    public function hasThumbnails()
    {
        return $this->getData()->hasThumbnails();
    }
}

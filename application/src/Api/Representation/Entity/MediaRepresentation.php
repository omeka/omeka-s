<?php
namespace Omeka\Api\Representation\Entity;

class MediaRepresentation extends AbstractResourceEntityRepresentation
{
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
            'o:source' => $this->source(),
            'o:filename' => $this->filename(),
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
     * Get the media's filename (if any).
     *
     * @return string|null
     */
    public function filename()
    {
        return $this->getData()->getFilename();
    }
}

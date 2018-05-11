<?php
namespace Omeka\Api\Representation;

class ItemRepresentation extends AbstractResourceEntityRepresentation
{
    /**
     * {@inheritDoc}
     */
    public function getControllerName()
    {
        return 'item';
    }

    /**
     * {@inheritDoc}
     */
    public function getResourceJsonLdType()
    {
        return 'o:Item';
    }

    /**
     * {@inheritDoc}
     */
    public function getResourceJsonLd()
    {
        $media = [];
        foreach ($this->media() as $mediaRepresentation) {
            $media[] = $mediaRepresentation->getReference();
        }
        $itemSets = [];
        foreach ($this->itemSets() as $itemSetRepresentation) {
            $itemSets[] = $itemSetRepresentation->getReference();
        }
        return [
            'o:media' => $media,
            'o:item_set' => $itemSets,
        ];
    }

    /**
     * Get the media associated with this item.
     *
     * @return array Array of MediaRepresentations
     */
    public function media()
    {
        $media = [];
        $mediaAdapter = $this->getAdapter('media');
        foreach ($this->resource->getMedia() as $mediaEntity) {
            $media[] = $mediaAdapter->getRepresentation($mediaEntity);
        }
        return $media;
    }

    /**
     * Get the item sets associated with this item.
     *
     * @return array Array of ItemSetRepresentations
     */
    public function itemSets()
    {
        $itemSets = [];
        $itemSetAdapter = $this->getAdapter('item_sets');
        foreach ($this->resource->getItemSets() as $itemSetEntity) {
            $itemSets[$itemSetEntity->getId()] =
                $itemSetAdapter->getRepresentation($itemSetEntity);
        }
        return $itemSets;
    }

    /**
     * {@inheritDoc}
     */
    public function primaryMedia()
    {
        // Return the first media if one exists.
        $media = $this->media();
        $properThumbnail = $this->getFirstProperThumbnailIndex();
        if ($properThumbnail && $media) {
            $thumbnail = $media[$properThumbnail];
            } elseif ($media) {
                $thumbnail = $media[0];
            } else {
                $thumbnail = null;
            }
        return $thumbnail;
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
                'controller' => 'item',
                'id' => $this->id(),
            ],
            ['force_canonical' => $canonical]
        );
    }

    /**
     * Get the index of the first proper item thumbnail
     *
     * @return integer Index of first proper thumbnail
     */
    private function getFirstProperThumbnailIndex()
    {
        $media = [];
        foreach ($this->media() as $mediaRepresentation) {
            $media[] = $mediaRepresentation->mediaType();
        }
        $thumbnails = [];
        foreach ($media as $index =>$value) {
            if (is_null($value) === false) {
                $prefix = explode("/",$value);
                if($prefix[0] == "image" || $prefix[1] == "pdf") {
                    $thumbnails[] = $index;
                }          
            }
        }
        if (!empty($thumbnails)) {
            return $thumbnails[0];
        }
    }
}

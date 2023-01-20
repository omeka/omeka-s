<?php
namespace Omeka\Api\Representation;

class ItemRepresentation extends AbstractResourceEntityRepresentation
{
    public function getControllerName()
    {
        return 'item';
    }

    public function getResourceJsonLdType()
    {
        return 'o:Item';
    }

    public function getResourceJsonLd()
    {
        $primaryMedia = null;
        if ($this->primaryMedia()) {
            $primaryMedia = $this->primaryMedia()->getReference();
        }
        $media = [];
        foreach ($this->media() as $mediaRepresentation) {
            $media[] = $mediaRepresentation->getReference();
        }
        $itemSets = [];
        foreach ($this->itemSets() as $itemSetRepresentation) {
            $itemSets[] = $itemSetRepresentation->getReference();
        }
        $sites = [];
        foreach ($this->sites() as $siteRepresentation) {
            $sites[] = $siteRepresentation->getReference();
        }
        return [
            'o:primary_media' => $primaryMedia,
            'o:media' => $media,
            'o:item_set' => $itemSets,
            'o:site' => $sites,
        ];
    }

    /**
     * Get the media associated with this item.
     *
     * @return MediaRepresentation[]
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
     * @return ItemSetRepresentation[]
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

    public function sites()
    {
        $sites = [];
        $siteAdapter = $this->getAdapter('sites');
        foreach ($this->resource->getSites() as $siteEntity) {
            $sites[$siteEntity->getId()] =
                $siteAdapter->getRepresentation($siteEntity);
        }
        return $sites;
    }

    public function primaryMedia()
    {
        // Return the primary media if one is set.
        $primaryMedia = $this->resource->getPrimaryMedia();
        if ($primaryMedia) {
            // The media may not be public, so fetch the media directly from the
            // entity manager to leverage the resource visibility filter.
            // Otherwise, an EntityNotFound exception will be raised when
            // attempting to fetch data from the Doctrine proxy returned from
            // getPrimaryMedia().
            $primaryMedia = $this->getServiceLocator()
                ->get('Omeka\EntityManager')
                ->getRepository('Omeka\Entity\Media')
                ->findOneBy(['id' => $primaryMedia->getId()]);
            return $this->getAdapter('media')->getRepresentation($primaryMedia);
        }
        // Return the first media if one exists.
        $media = $this->media();
        return $media ? $media[0] : null;
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

    public function sitePages($siteId)
    {
        return $this->getServiceLocator()
            ->get('Omeka\ApiManager')
            ->search('site_pages', ['site_id' => $siteId, 'item_id' => $this->id()])
            ->getContent();
    }
}

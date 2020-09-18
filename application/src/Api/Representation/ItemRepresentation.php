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

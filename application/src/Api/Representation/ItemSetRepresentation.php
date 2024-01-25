<?php
namespace Omeka\Api\Representation;

class ItemSetRepresentation extends AbstractResourceEntityRepresentation
{
    public function getControllerName()
    {
        return 'item-set';
    }

    public function getResourceJsonLdType()
    {
        return 'o:ItemSet';
    }

    public function getResourceJsonLd()
    {
        $sites = [];
        foreach ($this->sites() as $siteRepresentation) {
            $sites[] = $siteRepresentation->getReference();
        }

        $url = $this->getViewHelper('Url');
        $itemsUrl = $url(
            'api/default',
            ['resource' => 'items'],
            [
                'force_canonical' => true,
                'query' => ['item_set_id' => $this->id()],
            ]
        );
        return [
            'o:is_open' => $this->isOpen(),
            'o:items' => ['@id' => $itemsUrl],
            'o:site' => $sites,
        ];
    }

    /**
     * Get this set's item count.
     *
     * @return int
     */
    public function itemCount()
    {
        $response = $this->getServiceLocator()->get('Omeka\ApiManager')
            ->search('items', [
                'item_set_id' => $this->id(),
                'limit' => 0,
            ]);
        return $response->getTotalResults();
    }

    /**
     * Get whether this set is open or not open.
     *
     * @return bool
     */
    public function isOpen()
    {
        return $this->resource->isOpen();
    }

    /**
     * Return the first media of the first item.
     *
     * {@inheritDoc}
     */
    public function primaryMedia()
    {
        $itemEntities = $this->resource->getItems();
        if ($itemEntities->isEmpty()) {
            return null;
        }
        $item = $this->getAdapter('items')
            ->getRepresentation($itemEntities->slice(0, 1)[0]);
        return $item->primaryMedia();
    }

    public function siteUrl($siteSlug = null, $canonical = false)
    {
        if (!$siteSlug) {
            $siteSlug = $this->getServiceLocator()->get('Application')
                ->getMvcEvent()->getRouteMatch()->getParam('site-slug');
        }
        $url = $this->getViewHelper('Url');
        return $url(
            'site/item-set',
            [
                'site-slug' => $siteSlug,
                'item-set-id' => $this->id(),
            ],
            ['force_canonical' => $canonical]
        );
    }

    public function sites()
    {
        $sites = [];
        $siteAdapter = $this->getAdapter('sites');
        foreach ($this->resource->getSiteItemSets() as $siteItemSetEntity) {
            $siteEntity = $siteItemSetEntity->getSite();
            $sites[$siteEntity->getId()] =
                $siteAdapter->getRepresentation($siteEntity);
        }
        return $sites;
    }
}

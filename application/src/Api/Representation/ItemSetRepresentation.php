<?php
namespace Omeka\Api\Representation;

class ItemSetRepresentation extends AbstractResourceEntityRepresentation
{
    /**
     * {@inheritDoc}
     */
    public function getControllerName()
    {
        return 'item-set';
    }

    /**
     * {@inheritDoc}
     */
    public function getResourceJsonLdType()
    {
        return 'o:ItemSet';
    }

    /**
     * {@inheritDoc}
     */
    public function getResourceJsonLd()
    {
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
}

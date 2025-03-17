<?php
namespace Collecting\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class CollectingUserRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLdType()
    {
        return 'o-module-collecting:User';
    }

    public function getJsonLd()
    {
        if ($user = $this->user()) {
            $user = $user->getReference();
        }
        return [
            'o:user' => $user,
            'o-module-collecting:item' => $this->collectingItems(),
        ];
    }

    public function user()
    {
        return $this->getAdapter('users')
            ->getRepresentation($this->resource->getUser());
    }

    public function collectingItems()
    {
        $items = [];
        $adapter = $this->getAdapter('collecting_items');
        foreach ($this->resource->getCollectingItems() as $item) {
            $items[] = $adapter->getRepresentation($item);
        }
        return $items;
    }
}

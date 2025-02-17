<?php
namespace Mapping\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class MappingAdapter extends AbstractEntityAdapter
{
    public function getResourceName()
    {
        return 'mappings';
    }

    public function getRepresentationClass()
    {
        return 'Mapping\Api\Representation\MappingRepresentation';
    }

    public function getEntityClass()
    {
        return 'Mapping\Entity\Mapping';
    }

    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        $data = $request->getContent();
        if (Request::CREATE === $request->getOperation()
            && isset($data['o:item']['o:id'])
        ) {
            $item = $this->getAdapter('items')->findEntity($data['o:item']['o:id']);
            $entity->setItem($item);
        }
        if ($this->shouldHydrate($request, 'o-module-mapping:bounds')) {
            $entity->setBounds($request->getValue('o-module-mapping:bounds'));
        }
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        if (!$entity->getItem()) {
            $errorStore->addError('o:item', 'A mapping zone must have an item.'); // @translate
        }
        $bounds = $entity->getBounds();
        if (null !== $bounds
            && 4 !== count(array_filter(explode(',', $bounds), 'is_numeric'))
        ) {
            $errorStore->addError('o-module-mapping:bounds', 'Map bounds must contain four numbers separated by commas'); // @translate
        }
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['item_id'])) {
            $items = $query['item_id'];
            if (!is_array($items)) {
                $items = [$items];
            }
            $items = array_filter($items, 'is_numeric');

            if ($items) {
                $itemAlias = $this->createAlias();
                $qb->innerJoin(
                    'omeka_root.item', $itemAlias,
                    'WITH', $qb->expr()->in("$itemAlias.id", $this->createNamedParameter($qb, $items))
                );
            }
        }
    }
}

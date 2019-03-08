<?php
namespace Omeka\Api\Adapter;

use Doctrine\Common\Collections\Criteria;
use Omeka\Api\Request;
use Omeka\Entity\Property;
use Omeka\Entity\Resource;

class ResourceTitleHydrator
{
    /**
     * Hydrate the title of the resource entity.
     *
     * Attempts to get the title value of the resource template's title property
     * first. If that's not available, attempts to get the title value of the
     * dcterms:title property.
     *
     * @param Request $request
     * @param Resource $entity
     * @param AbstractResourceEntityAdapter $adapter
     */
    public function hydrate(Request $request, Resource $entity,
        AbstractResourceEntityAdapter $adapter
    ) {
        $title = null;
        $resourceTemplate = $entity->getResourceTemplate();
        if ($resourceTemplate && $resourceTemplate->getTitleProperty()) {
            $titleProperty = $resourceTemplate->getTitleProperty();
            $title = $this->getResourceTitle($titleProperty, $entity);
        }
        if (null === $title) {
            $titleProperty = $adapter->getPropertyByTerm('dcterms:title');
            $title = $this->getResourceTitle($titleProperty, $entity);
        }
        $entity->setTitle($title);
    }

    /**
     * Get the resource title.
     *
     * @param Property $titleProperty
     * @param Resource $entity
     * @return string|null
     */
    public function getResourceTitle(Property $titleProperty, Resource $entity)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('property', $titleProperty))
            ->andWhere(Criteria::expr()->neq('value', null))
            ->andWhere(Criteria::expr()->neq('value', ''))
            ->setMaxResults(1);
        $titleValues = $entity->getValues()->matching($criteria);
        return $titleValues->isEmpty() ? null : $titleValues->first()->getValue();
    }
}

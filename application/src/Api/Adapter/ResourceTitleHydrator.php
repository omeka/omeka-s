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
     * default title property (e.g. dcterms:title).
     *
     * @param Resource $entity
     * @param Property $defaultTitleProperty
     */
    public function hydrate(Resource $entity, Property $defaultTitleProperty)
    {
        $title = null;
        $resourceTemplate = $entity->getResourceTemplate();
        if ($resourceTemplate && $resourceTemplate->getTitleProperty()) {
            $title = $this->getResourceTitle($entity, $resourceTemplate->getTitleProperty());
        }
        if (null === $title) {
            $title = $this->getResourceTitle($entity, $defaultTitleProperty);
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
    public function getResourceTitle(Resource $entity, Property $titleProperty)
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

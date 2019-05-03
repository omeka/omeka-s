<?php
namespace Omeka\Api\Adapter;

use Doctrine\Common\Collections\Criteria;
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
        $resourceTemplate = $entity->getResourceTemplate();
        if ($resourceTemplate && ($templateTitleProperty = $resourceTemplate->getTitleProperty())) {
            $titleProperty = $templateTitleProperty;
        } else {
            $titleProperty = $defaultTitleProperty;
        }
        $entity->setTitle($this->getResourceTitle($entity, $titleProperty));
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

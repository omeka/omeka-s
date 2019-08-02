<?php
namespace Omeka\Api\Adapter;

use Doctrine\Common\Collections\Criteria;
use Omeka\Api\Representation\ValueRepresentation;
use Omeka\Entity\Property;
use Omeka\Entity\Resource;
use Zend\ServiceManager\ServiceLocatorInterface;

class ResourceTitleHydrator
{
    /**
     * @var ServiceLocatorInterface $services
     */
    protected $services;

    /**
     * @param ServiceLocatorInterface $services
     */
    public function __construct(ServiceLocatorInterface $services)
    {
        $this->services = $services;
    }

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
        $dataTypes = $this->services->get('Omeka\DataTypeManager');
        $view = $this->services->get('ViewRenderer');
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('property', $titleProperty))
            ->setMaxResults(1);
        $titleValues = $entity->getValues()->matching($criteria);
        $title = null;
        if (!$titleValues->isEmpty()) {
            $value = $titleValues->first();
            $valueRepresentation = new ValueRepresentation($value, $this->services);
            $title = $dataTypes->get($value->getType())->getTitleText($view, $valueRepresentation);
        }
        return $title;
    }
}

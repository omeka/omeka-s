<?php
namespace Omeka\Api\Representation;

use Omeka\Entity\SiteItemSet;
use Zend\ServiceManager\ServiceLocatorInterface;

class SiteItemSetRepresentation extends AbstractRepresentation
{
    /**
     * @var SiteItemSet
     */
    protected $itemSet;

    /**
     * Construct the site item set representation object.
     *
     * @param SiteItemSet $itemSet
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct(SiteItemSet $itemSet, ServiceLocatorInterface $serviceLocator)
    {
        $this->setServiceLocator($serviceLocator);
        $this->itemSet = $itemSet;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return [
            'o:item_set' => $this->itemSet()->getReference(),
        ];
    }

    /**
     * @return SiteRepresentation
     */
    public function site()
    {
        return $this->getAdapter('sites')
            ->getRepresentation($this->itemSet->getSite());
    }

    /**
     * @return ItemSetRepresentation
     */
    public function itemSet()
    {
        return $this->getAdapter('item_sets')
            ->getRepresentation($this->itemSet->getItemSet());
    }
}

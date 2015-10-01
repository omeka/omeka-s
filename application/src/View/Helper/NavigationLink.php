<?php
namespace Omeka\View\Helper;

use Omeka\Site\Navigation\Link\Manager;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Helper\AbstractHelper;

class NavigationLink extends AbstractHelper
{
    /**
     * @var Manager
     */
    protected $manager;

    /**
     * Construct the helper.
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->manager = $serviceLocator->get('Omeka\Site\NavigationLinkManager');
    }

    public function getTypes()
    {
        return array_unique($this->manager->getCanonicalNames());
    }

    public function getCustomTypes()
    {
        return array_diff($this->getTypes(), array('page'));
    }

    public function getLabel($type)
    {
        return $this->manager->get($type)->getLabel();
    }
}

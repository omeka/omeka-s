<?php
namespace Omeka\View\Helper;

use Omeka\Service\NavigationFactory;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Helper\AbstractHelper;

class Nav extends AbstractHelper
{
    /**
     * Construct the helper.
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * Return navigation by configured name.
     *
     * Proxies Zend's navigation view helper, but instead of passing a factory
     * name, pass a name from the navigation configuration.
     *
     * @param string $name Navigation configuration key
     * @return \Zend\View\Helper\Navigation
     */
    public function __invoke($name)
    {
        // Must instantiate the navigation factory directly so we can set the
        // navigation name prior to creating the service. We must still use the
        // factory to compose the navigation object becuase it is responsibe for
        // injecting dependencies into the pages.
        $factory = new NavigationFactory;
        $factory->setName($name);
        $navigation = $factory->createService($this->serviceLocator);
        return $this->getView()->navigation($navigation);
    }
}

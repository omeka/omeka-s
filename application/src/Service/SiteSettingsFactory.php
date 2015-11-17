<?php
namespace Omeka\Service;

use Omeka\Settings\SiteSettings;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class SiteSettingsFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $routeMatch = $serviceLocator->get('Application')->getMvcEvent()->getRouteMatch();
        if (!$routeMatch->getParam('__SITE__')
            && !$routeMatch->getParam('__SITEADMIN__')
        ) {
            throw new Exception\RuntimeException('Cannot invoke site settings when not within a site context');
        }
        $site = $serviceLocator->get('Omeka\EntityManager')
            ->createQuery('SELECT s FROM Omeka\Entity\Site s WHERE s.slug = :slug')
            ->setParameter('slug', $routeMatch->getParam('site-slug'))
            ->getOneOrNullResult();
        if (!$site) {
            throw new Exception\RuntimeException('Cannot invoke site settings when site does not exist');
        }
        return new SiteSettings($site);
    }
}

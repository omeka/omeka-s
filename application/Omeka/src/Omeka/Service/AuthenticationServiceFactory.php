<?php
namespace Omeka\Service;

use Omeka\Authentication\Adapter\PasswordAdapter;
use Omeka\Authentication\Storage\DoctrineWrapper;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Storage\Session;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Authentication service factory.
 */
class AuthenticationServiceFactory implements FactoryInterface
{
    /**
     * Create the authentication service.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return ApiManager
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $em = $serviceLocator->get('Omeka\EntityManager');
        $repository = $em->getRepository('Omeka\Model\Entity\User');

        $storage = new DoctrineWrapper(new Session, $repository);
        $adapter = new PasswordAdapter($repository);

        $authService = new AuthenticationService($storage, $adapter);
        return $authService;
    }
}

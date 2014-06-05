<?php
namespace Omeka\Service;

use Omeka\Authentication\Adapter\KeyAdapter;
use Omeka\Authentication\Adapter\PasswordAdapter;
use Omeka\Authentication\Storage\DoctrineWrapper;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Storage\NonPersistent;
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
        $entityManager = $serviceLocator->get('Omeka\EntityManager');
        $status = $serviceLocator->get('Omeka\Status');
        $userRepository = $entityManager->getRepository('Omeka\Model\Entity\User');

        if ($status->isApiRequest()) {
            // Authenticate using key for API requests.
            $keyRepository = $entityManager->getRepository('Omeka\Model\Entity\Key');
            $storage = new DoctrineWrapper(new NonPersistent, $userRepository);
            $adapter = new KeyAdapter($keyRepository, $entityManager);
        } else {
            // Authenticate using user/password for all other requests.
            $storage = new DoctrineWrapper(new Session, $userRepository);
            $adapter = new PasswordAdapter($userRepository);
        }

        $authService = new AuthenticationService($storage, $adapter);
        return $authService;
    }
}

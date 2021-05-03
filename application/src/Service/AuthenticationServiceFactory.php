<?php
namespace Omeka\Service;

use Omeka\Authentication\Adapter\KeyAdapter;
use Omeka\Authentication\Adapter\PasswordAdapter;
use Omeka\Authentication\Storage\DoctrineWrapper;
use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Adapter\Callback;
use Laminas\Authentication\Storage\NonPersistent;
use Laminas\Authentication\Storage\Session;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * Authentication service factory.
 */
class AuthenticationServiceFactory implements FactoryInterface
{
    /**
     * Create the authentication service.
     *
     * @return AuthenticationService
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $status = $serviceLocator->get('Omeka\Status');

        // Skip auth retrieval entirely if we're installing or migrating.
        if (!$status->isInstalled() ||
            ($status->needsVersionUpdate() && $status->needsMigration())
        ) {
            $storage = new NonPersistent;
            $adapter = new Callback(function () {
                return null;
            });
        } else {
            $entityManager = $serviceLocator->get('Omeka\EntityManager');
            $userRepository = $entityManager->getRepository('Omeka\Entity\User');

            $useApiKeyAuthentication = $status->isApiRequest();
            if ($useApiKeyAuthentication) {
                $request = $serviceLocator->get('Application')->getMvcEvent()->getRequest();
                $useApiKeyAuthentication = $request->getQuery('key_identity') !== null
                    && $request->getQuery('key_credential') !== null;
            }

            if ($useApiKeyAuthentication) {
                // Authenticate using key for API requests with credentials.
                $keyRepository = $entityManager->getRepository('Omeka\Entity\ApiKey');
                $storage = new DoctrineWrapper(new NonPersistent, $userRepository);
                $adapter = new KeyAdapter($keyRepository, $entityManager);
            } else {
                // Authenticate using user/password for all other requests.
                // The session storage is used for api requests too when
                // credentials are not provided.
                $storage = new DoctrineWrapper(new Session, $userRepository);
                $adapter = new PasswordAdapter($userRepository);
            }
        }

        return new AuthenticationService($storage, $adapter);
    }
}

<?php
namespace Omeka\Service;

use Omeka\Api\Request as ApiRequest;
use Omeka\Event\Event;
use Omeka\Permissions\Acl;
use Omeka\Service\Exception;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Access control list factory.
 */
class AclFactory implements FactoryInterface
{
    /**
     * Create the access control list.
     * 
     * @param ServiceLocatorInterface $serviceLocator
     * @return Acl
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $acl = new Acl;

        $auth = $serviceLocator->get('Omeka\AuthenticationService');
        $acl->setAuthenticationService($auth);

        $this->addRoles($acl, $serviceLocator);
        $this->addResources($acl, $serviceLocator);
        $this->addRules($acl, $serviceLocator);

        if (!$serviceLocator->get('Omeka\Status')->isInstalled()) {
            // Allow all privileges during installation.
            $acl->allow();
            return $acl;
        }

        // Trigger the acl event.
        $event = new Event(Event::ACL, $acl, array('services' => $serviceLocator));
        $serviceLocator->get('EventManager')->trigger($event);

        return $acl;
    }

    /**
     * Add ACL roles.
     *
     * @param Acl $acl
     * @param ServiceLocatorInterface $serviceLocator
     */
    protected function addRoles(Acl $acl, ServiceLocatorInterface $serviceLocator)
    {
        $acl->addRole(Acl::ROLE_GUEST)
            ->addRole(Acl::ROLE_RESEARCHER)
            ->addRole(Acl::ROLE_AUTHOR)
            ->addRole(Acl::ROLE_REVIEWER)
            ->addRole(Acl::ROLE_EDITOR)
            ->addRole(Acl::ROLE_SITE_ADMIN)
            ->addRole(Acl::ROLE_GLOBAL_ADMIN);
    }

    /**
     * Add ACL resources.
     *
     * The following resources are added automatically:
     * 
     * - API adapter classes that implement ResourceInterface
     * - Entity classes that implement ResourceInterface
     * - Controller classes
     *
     * @param Acl $acl
     * @param ServiceLocatorInterface $serviceLocator
     */
    protected function addResources(Acl $acl, ServiceLocatorInterface $serviceLocator)
    {
        // Add API adapters as ACL resources. These resources are used to set
        // rules for general access to API resources.
        $config = $serviceLocator->get('Config');
        if (!isset($config['api_adapters']['invokables'])
            || !is_array($config['api_adapters']['invokables'])
        ) {
            throw new Exception\ConfigException('Missing API adapter configuration');
        }
        foreach ($config['api_adapters']['invokables'] as $adapterClass) {
            $acl->addResource($adapterClass);
        }

        // Add Doctrine entities as ACL resources. These resources are used to
        // set rules for access to specific entities.
        $entities = $serviceLocator->get('Omeka\EntityManager')->getConfiguration()
            ->getMetadataDriverImpl()->getAllClassNames();
        foreach ($entities as $entityClass) {
            if (is_subclass_of($entityClass, 'Zend\Permissions\Acl\Resource\ResourceInterface')) {
                $acl->addResource($entityClass);
            }
        }

        // Add controllers as ACL resources. These rules are used to set rules
        // for access to controllers and their actions.
        $controllers = array_keys($serviceLocator->get('ControllerLoader')
            ->getCanonicalNames());
        foreach ($controllers as $controller) {
            $acl->addResource($controller);
        }
    }

    /**
     * Add ACL rules.
     *
     * @param Acl $acl
     * @param ServiceLocatorInterface $serviceLocator
     */
    protected function addRules(Acl $acl, ServiceLocatorInterface $serviceLocator)
    {
        // Global admins have access to all resources.
        $acl->allow(Acl::ROLE_GLOBAL_ADMIN);

        // Everyone has access to these resources.
        $acl->allow(null, 'Omeka\Controller\Api');
        $acl->allow(null, 'Omeka\Controller\Login');
        $acl->allow(null, 'Omeka\Controller\Maintenance');
        $acl->allow(null, 'Omeka\Controller\Migrate');

        // Add guest rules.
        $acl->allow(Acl::ROLE_GUEST, null, array(
            ApiRequest::SEARCH,
            ApiRequest::READ,
        ));
        $acl->deny(Acl::ROLE_GUEST, array(
            'Omeka\Api\Adapter\ModuleAdapter',
            'Omeka\Api\Adapter\Entity\JobAdapter',
            'Omeka\Api\Adapter\Entity\UserAdapter',
        ), array(
            ApiRequest::SEARCH,
            ApiRequest::READ,
        ));
    }
}

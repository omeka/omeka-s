<?php
namespace Omeka\Service;

use Omeka\Api\Request as ApiRequest;
use Omeka\Event\Event;
use Omeka\Permissions\Acl;
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

        $this->addRoles($acl, $serviceLocator);
        $this->addResources($acl, $serviceLocator);
        $this->addRules($acl, $serviceLocator);

        if (!$serviceLocator->get('Omeka\InstallationManager')->isInstalled()) {
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
        // Add ACL roles.
        $acl->addRole('guest')
            ->addRole('item_creator', 'guest')
            ->addRole('site_admin')
            ->addRole('global_admin');

        // Set the logged in user as the current_user role.
        $auth = $serviceLocator->get('Omeka\AuthenticationService');
        if ($auth->hasIdentity()) {
            $currentUser = $auth->getIdentity();
            $acl->addRole($currentUser, $currentUser->getRole());
        } else {
            $acl->addRole('current_user', 'guest');
        }
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
        $apiResources = $serviceLocator->get('Omeka\ApiManager')->getResources();
        foreach ($apiResources as $adapterClass) {
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
        $acl->allow('global_admin');

        // Site admins have access to all resources.
        $acl->allow('site_admin');

        // Everyone has access to the API.
        $acl->allow(null, 'Omeka\Controller\Api');

        // Everyone has access to login.
        $acl->allow(null, 'Omeka\Controller\Login');

        // Add guest rules.
        $acl->allow('guest', null, array(
            ApiRequest::SEARCH,
            ApiRequest::READ,
        ));
        $acl->deny('guest', array(
            'Omeka\Api\Adapter\Entity\UserAdapter',
            'Omeka\Api\Adapter\ModuleAdapter',
            'Omeka\Api\Adapter\OptionAdapter',
        ), array(
            ApiRequest::SEARCH,
            ApiRequest::READ,
        ));

        // Add item_creator rules.
        $acl->allow('item_creator', 'Omeka\Api\Adapter\Entity\ItemAdapter', array(
            ApiRequest::CREATE,
            ApiRequest::UPDATE,
            ApiRequest::DELETE,
        ));
    }
}

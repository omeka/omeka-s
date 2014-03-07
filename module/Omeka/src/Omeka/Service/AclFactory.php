<?php
namespace Omeka\Service;

use Omeka\Api\Adapter\Entity\EntityAdapterInterface;
use Omeka\Api\Request as ApiRequest;
use Omeka\Event\Event;
use Omeka\Stdlib\ClassCheck;
use Zend\Permissions\Acl\Acl;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Access control list factory.
 */
class AclFactory implements FactoryInterface, ServiceLocatorAwareInterface
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * Create the access control list.
     * 
     * @param ServiceLocatorInterface $serviceLocator
     * @return Acl
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $this->setServiceLocator($serviceLocator);
        $acl = new Acl;

        $this->addRoles($acl);
        $this->addResources($acl);
        $this->addRules($acl);

        // Trigger the acl event.
        $event = new Event('acl', $acl, array('services' => $serviceLocator));
        $serviceLocator->get('EventManager')->trigger($event);

        return $acl;
    }

    /**
     * Add ACL roles.
     *
     * @param Acl $acl
     */
    protected function addRoles(Acl $acl)
    {
        // Add ACL roles.
        $acl->addRole('guest')
            ->addRole('item_creator', 'guest')
            ->addRole('site_admin')
            ->addRole('global_admin');

        // Set the logged in user as the current_user role.
        $auth = $this->getServiceLocator()->get('AuthenticationService');
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
     * - Controller classes
     * - API adapter classes that implement ResourceInterface
     * - Entity classes that implement ResourceInterface
     *
     * @param Acl $acl
     */
    protected function addResources(Acl $acl)
    {
        $config = $this->getServiceLocator()->get('Config');
        $api = $this->getServiceLocator()->get('ApiManager');

        // Add API resources as ACL resources.
        foreach ($api->getResources() as $adapterClass) {

            // Add API adapters as ACL resources. These resources are used to
            // set rules for general access to API resources.
            if (ClassCheck::isInterfaceOf(
                'Zend\Permissions\Acl\Resource\ResourceInterface',
                $adapterClass
            )) {
                $acl->addResource($adapterClass);
                $adapter = new $adapterClass;

                // Add corresponding entities as ACL resources. These resources
                // are used to set rules for access to specific entities.
                if ($adapter instanceof EntityAdapterInterface
                    && ClassCheck::isInterfaceOf(
                        'Zend\Permissions\Acl\Resource\ResourceInterface',
                        $adapter->getEntityClass()
                )) {
                    $acl->addResource($adapter->getEntityClass());
                }
            }
        }

        // Add controllers as ACL resources.
        $controllers = array_merge(
            array_keys($config['controllers']['invokables']),
            isset($config['controllers']['factories'])
                ? array_keys($config['controllers']['factories']) : array()
        );
        foreach ($controllers as $controller) {
            $acl->addResource($controller);
        }
    }

    /**
     * Add ACL rules.
     *
     * @param Acl $acl
     */
    protected function addRules(Acl $acl)
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
            'Omeka\Api\Adapter\Entity\ModuleAdapter',
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

    /**
     * {@inheritDoc}
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->services = $serviceLocator;
    }

    /**
     * {@inheritDoc}
     */
    public function getServiceLocator()
    {
        return $this->services;
    }
}

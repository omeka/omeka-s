<?php
namespace Omeka\Service;

use Omeka\Api\Adapter\Entity\EntityAdapterInterface;
use Omeka\Api\Request as ApiRequest;
use Omeka\Stdlib\ClassCheck;
use Zend\Permissions\Acl\Acl;
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
        $config = $serviceLocator->get('Config');
        if (!isset($config['api_manager']['resources'])) {
            throw new Exception\ConfigException('The configuration has no registered API resources.');
        }

        $acl = new Acl;

        // Add ACL roles.
        $acl->addRole('guest')
            ->addRole('item_creator', 'guest')
            ->addRole('site_admin')
            ->addRole('global_admin');

        // Set the logged in user as the current_user role.
        $auth = $serviceLocator->get('AuthenticationService');
        if ($auth->hasIdentity()) {
            $currentUser = $auth->getIdentity();
            $acl->addRole($currentUser, $currentUser->getRole());
        } else {
            $acl->addRole('current_user', 'guest');
        }

        // Add ACL resources.
        foreach ($config['api_manager']['resources'] as $resource => $config) {

            // Add API adapters as ACL resources. These resources are used to
            // set rules for general access to API resources.
            if (ClassCheck::isInterfaceOf(
                'Zend\Permissions\Acl\Resource\ResourceInterface',
                $config['adapter_class']
            )) {
                $acl->addResource($config['adapter_class']);
                $adapter = new $config['adapter_class'];

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

        // Set ACL rules.
        $acl->allow('guest', null, array(
            ApiRequest::SEARCH,
            ApiRequest::READ,
        ));
        // Deny guests access to search or read users.
        $acl->deny(
            'guest',
            array(
                'Omeka\Api\Adapter\Entity\UserAdapter',
                'Omeka\Api\Adapter\Entity\ModuleAdapter',
            ),
            array(
                ApiRequest::SEARCH,
                ApiRequest::READ,
            )
        );
        // Allow item creators access to create, update, and delete items.
        $acl->allow('item_creator', 'Omeka\Api\Adapter\Entity\ItemAdapter', array(
            ApiRequest::CREATE,
            ApiRequest::UPDATE,
            ApiRequest::DELETE,
        ));
        $acl->allow('site_admin');
        $acl->allow('global_admin');

        return $acl;
    }
}

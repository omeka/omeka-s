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

        if ($serviceLocator->get('Omeka\Status')->isInstalled()) {
            // Omeka is installed. Set rules and trigger the acl event.
            $this->addRules($acl, $serviceLocator);
            $event = new Event(Event::ACL, $acl, array('services' => $serviceLocator));
            $serviceLocator->get('EventManager')->trigger($event);
        } else {
            // Allow all privileges during installation.
            $acl->allow();
        }

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
        $acl->addRole(Acl::ROLE_RESEARCHER)
            ->addRole(Acl::ROLE_AUTHOR, Acl::ROLE_RESEARCHER)
            ->addRole(Acl::ROLE_REVIEWER, Acl::ROLE_AUTHOR)
            ->addRole(Acl::ROLE_EDITOR, Acl::ROLE_REVIEWER)
            ->addRole(Acl::ROLE_SITE_ADMIN, Acl::ROLE_EDITOR)
            ->addRole(Acl::ROLE_GLOBAL_ADMIN, Acl::ROLE_SITE_ADMIN);
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
        $config = $serviceLocator->get('Config');

        // Add resources from configuration.
        if (isset($config['permissions']['acl_resources'])
            && is_array($config['permissions']['acl_resources'])
        ) {
            foreach ($config['permissions']['acl_resources'] as $resource) {
                $acl->addResource($resource);
            }
        }

        // Add API adapters as ACL resources. These resources are used to set
        // rules for general access to API resources.
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
        // All roles:
        $acl->allow(null, array(
            'Omeka\Model\Entity\Item',
            'Omeka\Model\Entity\ItemSet',
            'Omeka\Model\Entity\Media',
            'Omeka\Model\Entity\Vocabulary',
        ), array(
            'read',
        ));
        $acl->allow(null, array(
            'Omeka\Api\Adapter\Entity\VocabularyAdapter',
            'Omeka\Api\Adapter\Entity\ResourceClassAdapter',
            'Omeka\Api\Adapter\Entity\ResourceTemplateAdapter',
            'Omeka\Api\Adapter\Entity\PropertyAdapter',
            'Omeka\Api\Adapter\Entity\ItemAdapter',
            'Omeka\Api\Adapter\Entity\MediaAdapter',
            'Omeka\Api\Adapter\Entity\ItemSetAdapter',
            'Omeka\Api\Adapter\Entity\SiteAdapter',
        ), array(
            'search',
            'read',
        ));
        $acl->allow(null, array(
            'Omeka\Controller\Api',
            'Omeka\Controller\Login',
            'Omeka\Controller\Maintenance',
            'Omeka\Controller\Migrate',
        ));

        // ROLE_RESEARCHER
        $acl->allow(Acl::ROLE_RESEARCHER, array(
            'Omeka\Model\Entity\User',
        ), array(
            'read',
        ));
        $acl->allow(Acl::ROLE_RESEARCHER, array(
            'Omeka\Api\Adapter\Entity\UserAdapter',
        ), array(
            'search',
            'read',
        ));
        $acl->allow(Acl::ROLE_RESEARCHER, array(
            'Omeka\Controller\Admin\Item',
            'Omeka\Controller\Admin\ItemSet',
            'Omeka\Controller\Admin\Media',
            'Omeka\Controller\Admin\Vocabulary',
            'Omeka\Controller\Admin\ResourceTemplate',
            'Omeka\Controller\Admin\User',
        ), array(
            'index',
            'browse',
            'show',
        ));
        $acl->allow(Acl::ROLE_RESEARCHER,
            'Omeka\Controller\Admin\Vocabulary',
            array(
                'classes',
                'properties',
            )
        );

        // ROLE_AUTHOR

        // ROLE_REVIEWER

        // ROLE_EDITOR

        // ROLE_SITE_ADMIN

        // ROLE_GLOBAL_ADMIN
        $acl->allow(Acl::ROLE_GLOBAL_ADMIN);
    }
}

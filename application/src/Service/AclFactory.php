<?php
namespace Omeka\Service;

use Omeka\Api\Request as ApiRequest;
use Omeka\Event\Event;
use Omeka\Permissions\Acl;
use Omeka\Permissions\Assertion\IsSelfAssertion;
use Omeka\Permissions\Assertion\OwnsEntityAssertion;
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
        $this->addRulesForAllRoles($acl);
        $this->addRulesForResearcher($acl);
        $this->addRulesForAuthor($acl);
        $this->addRulesForReviewer($acl);
        $this->addRulesForEditor($acl);
        $this->addRulesForSiteAdmin($acl);
        $this->addRulesForGlobalAdmin($acl);
    }

    /**
     * Add rules for all roles, including users that aren't authenticated.
     *
     * @param Acl $acl
     */
    protected function addRulesForAllRoles(Acl $acl)
    {
        $acl->allow(
            null,
            array(
                'Omeka\Controller\Api',
                'Omeka\Controller\Login',
                'Omeka\Controller\Maintenance',
                'Omeka\Controller\Migrate',
            )
        );
        $acl->allow(
            null,
            array(
                'Omeka\Api\Adapter\Entity\ItemSetAdapter',
                'Omeka\Api\Adapter\Entity\ItemAdapter',
                'Omeka\Api\Adapter\Entity\MediaAdapter',
                'Omeka\Api\Adapter\Entity\VocabularyAdapter',
                'Omeka\Api\Adapter\Entity\ResourceClassAdapter',
                'Omeka\Api\Adapter\Entity\PropertyAdapter',
                'Omeka\Api\Adapter\Entity\ResourceTemplateAdapter',
            ),
            array(
                'search',
                'read',
            )
        );
        $acl->allow(
            null,
            array(
                'Omeka\Entity\ItemSet',
                'Omeka\Entity\Item',
                'Omeka\Entity\Media',
                'Omeka\Entity\Vocabulary',
                'Omeka\Entity\ResourceClass',
                'Omeka\Entity\Property',
                'Omeka\Entity\ResourceTemplate',
            ),
            array(
                'read',
            )
        );
    }

    /**
     * Add rules for "researcher" role.
     *
     * @param Acl $acl
     */
    protected function addRulesForResearcher(Acl $acl)
    {
        $acl->allow(
            'researcher',
            array(
                'Omeka\Controller\Admin\Index',
                'Omeka\Controller\Admin\Item',
                'Omeka\Controller\Admin\ItemSet',
                'Omeka\Controller\Admin\Media',
                'Omeka\Controller\Admin\ResourceTemplate',
                'Omeka\Controller\Admin\Vocabulary',
                'Omeka\Controller\Admin\ResourceClass',
                'Omeka\Controller\Admin\Property',
            ),
            array(
                'index',
                'browse',
                'show',
                'show-details',
                'classes', // from Vocabulary controller
                'properties', // from Vocabulary controller
            )
        );
        $acl->allow(
            'researcher',
            'Omeka\Controller\Admin\User',
            'edit'
        );
        $acl->allow(
            'researcher',
            'Omeka\Api\Adapter\Entity\UserAdapter',
            array('read', 'update')
        );
        $acl->allow(
            'researcher',
            'Omeka\Entity\User',
            array('read', 'update'),
            new IsSelfAssertion
        );
    }

    /**
     * Add rules for "author" role.
     *
     * @param Acl $acl
     */
    protected function addRulesForAuthor(Acl $acl)
    {
        $acl->allow(
            'author',
            array(
                'Omeka\Controller\Admin\Index',
                'Omeka\Controller\Admin\Item',
                'Omeka\Controller\Admin\ItemSet',
                'Omeka\Controller\Admin\Media',
                'Omeka\Controller\Admin\ResourceTemplate',
                'Omeka\Controller\Admin\Vocabulary',
                'Omeka\Controller\Admin\ResourceClass',
                'Omeka\Controller\Admin\Property',
            ),
            array(
                'index',
                'browse',
                'show',
                'show-details',
                'classes', // from Vocabulary controller
                'properties', // from Vocabulary controller
                'add-new-property-row', // from ResourceTemplate controller
            )
        );
        $acl->allow(
            'author',
            array(
                'Omeka\Controller\Admin\Item',
                'Omeka\Controller\Admin\ItemSet',
                'Omeka\Controller\Admin\Media',
                'Omeka\Controller\Admin\ResourceTemplate',
            ),
            array(
                'add',
                'edit',
                'delete',
            )
        );
        $acl->allow(
            'author',
            array(
                'Omeka\Api\Adapter\Entity\ItemAdapter',
                'Omeka\Api\Adapter\Entity\ItemSetAdapter',
                'Omeka\Api\Adapter\Entity\MediaAdapter',
                'Omeka\Api\Adapter\Entity\ResourceTemplateAdapter',
            ),
            array(
                'create',
                'update',
                'delete',
            )
        );
        $acl->allow(
            'author',
            array(
                'Omeka\Entity\Item',
                'Omeka\Entity\ItemSet',
                'Omeka\Entity\Media',
                'Omeka\Entity\ResourceTemplate',
            ),
            array(
                'create',
            )
        );
        $acl->allow(
            'author',
            array(
                'Omeka\Entity\Item',
                'Omeka\Entity\ItemSet',
                'Omeka\Entity\Media',
                'Omeka\Entity\ResourceTemplate',
            ),
            array(
                'update',
                'delete',
            ),
            new OwnsEntityAssertion
        );
        $acl->allow(
            'author',
            'Omeka\Controller\Admin\User',
            'edit'
        );
        $acl->allow(
            'author',
            'Omeka\Api\Adapter\Entity\UserAdapter',
            array('read', 'update')
        );
        $acl->allow(
            'author',
            'Omeka\Entity\User',
            array('read', 'update'),
            new IsSelfAssertion
        );
    }

    /**
     * Add rules for "reviewer" role.
     *
     * @param Acl $acl
     */
    protected function addRulesForReviewer(Acl $acl)
    {
        $acl->allow(
            'reviewer',
            array(
                'Omeka\Controller\Admin\Index',
                'Omeka\Controller\Admin\Item',
                'Omeka\Controller\Admin\ItemSet',
                'Omeka\Controller\Admin\Media',
                'Omeka\Controller\Admin\ResourceTemplate',
                'Omeka\Controller\Admin\Vocabulary',
                'Omeka\Controller\Admin\ResourceClass',
                'Omeka\Controller\Admin\Property',
            ),
            array(
                'index',
                'browse',
                'show',
                'show-details',
                'classes', // from Vocabulary controller
                'properties', // from Vocabulary controller
                'add-new-property-row', // from ResourceTemplate controller
            )
        );
        $acl->allow(
            'reviewer',
            array(
                'Omeka\Controller\Admin\Item',
                'Omeka\Controller\Admin\ItemSet',
                'Omeka\Controller\Admin\Media',
                'Omeka\Controller\Admin\ResourceTemplate',
            ),
            array(
                'add',
                'edit',
                'delete',
            )
        );
        $acl->allow(
            'reviewer',
            array(
                'Omeka\Api\Adapter\Entity\ItemAdapter',
                'Omeka\Api\Adapter\Entity\ItemSetAdapter',
                'Omeka\Api\Adapter\Entity\MediaAdapter',
                'Omeka\Api\Adapter\Entity\ResourceTemplateAdapter',
            ),
            array(
                'create',
                'update',
                'delete',
            )
        );
        $acl->allow(
            'reviewer',
            array(
                'Omeka\Entity\Item',
                'Omeka\Entity\ItemSet',
                'Omeka\Entity\Media',
            ),
            array(
                'create',
                'update',
            )
        );
        $acl->allow(
            'reviewer',
            array(
                'Omeka\Entity\Item',
                'Omeka\Entity\ItemSet',
                'Omeka\Entity\Media',
            ),
            array(
                'delete',
            ),
            new OwnsEntityAssertion
        );
        $acl->allow(
            'reviewer',
            'Omeka\Controller\Admin\User',
            'edit'
        );
        $acl->allow(
            'reviewer',
            'Omeka\Api\Adapter\Entity\UserAdapter',
            array('read', 'update')
        );
        $acl->allow(
            'reviewer',
            'Omeka\Entity\User',
            array('read', 'update'),
            new IsSelfAssertion
        );
    }

    /**
     * Add rules for "editor" role.
     *
     * @param Acl $acl
     */
    protected function addRulesForEditor(Acl $acl)
    {
        $acl->allow(
            'editor',
            array(
                'Omeka\Controller\Admin\Index',
                'Omeka\Controller\Admin\Item',
                'Omeka\Controller\Admin\ItemSet',
                'Omeka\Controller\Admin\Media',
                'Omeka\Controller\Admin\ResourceTemplate',
                'Omeka\Controller\Admin\Vocabulary',
                'Omeka\Controller\Admin\ResourceClass',
                'Omeka\Controller\Admin\Property',
            ),
            array(
                'index',
                'browse',
                'show',
                'show-details',
                'classes', // from Vocabulary controller
                'properties', // from Vocabulary controller
                'add-new-property-row', // from ResourceTemplate controller
            )
        );
        $acl->allow(
            'editor',
            array(
                'Omeka\Controller\Admin\Item',
                'Omeka\Controller\Admin\ItemSet',
                'Omeka\Controller\Admin\Media',
                'Omeka\Controller\Admin\ResourceTemplate',
            ),
            array(
                'add',
                'edit',
                'delete',
            )
        );
        $acl->allow(
            'editor',
            array(
                'Omeka\Api\Adapter\Entity\ItemAdapter',
                'Omeka\Api\Adapter\Entity\ItemSetAdapter',
                'Omeka\Api\Adapter\Entity\MediaAdapter',
                'Omeka\Api\Adapter\Entity\ResourceTemplateAdapter',
            ),
            array(
                'create',
                'update',
                'delete',
            )
        );
        $acl->allow(
            'editor',
            array(
                'Omeka\Entity\Item',
                'Omeka\Entity\ItemSet',
                'Omeka\Entity\Media',
                'Omeka\Entity\ResourceTemplate',
            ),
            array(
                'create',
                'update',
                'delete',
            )
        );
        $acl->allow(
            'editor',
            'Omeka\Controller\Admin\User'
        );
        $acl->allow(
            'editor',
            'Omeka\Api\Adapter\Entity\UserAdapter'
        );
        $acl->allow(
            'editor',
            'Omeka\Entity\User',
            array('read')
        );
        $acl->allow(
            'editor',
            'Omeka\Entity\User',
            array('create', 'update'),
            new IsSelfAssertion
        );
    }

    /**
     * Add rules for "site_admin" role.
     *
     * @param Acl $acl
     */
    protected function addRulesForSiteAdmin(Acl $acl)
    {
        $acl->allow('site_admin');
        $acl->deny(
            'site_admin',
            'Omeka\Module\Manager',
            array('activate', 'deactivate', 'install', 'uninstall', 'upgrade')
        );
        $acl->deny(
            'site_admin',
            'Omeka\Controller\Admin\Vocabulary',
            array('import')
        );
        $acl->deny(
            'site_admin',
            'Omeka\Api\Adapter\Entity\VocabularyAdapter',
            array('create', 'update', 'delete')
        );
        $acl->deny(
            'site_admin',
            'Omeka\Entity\Media',
            array('create', 'update', 'delete')
        );
    }

    /**
     * Add rules for "global_admin" role.
     *
     * @param Acl $acl
     */
    protected function addRulesForGlobalAdmin(Acl $acl)
    {
        $acl->allow('global_admin');
    }
}

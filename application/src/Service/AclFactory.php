<?php
namespace Omeka\Service;

use Interop\Container\ContainerInterface;
use Omeka\Permissions\Acl;
use Omeka\Permissions\Assertion\AssertionNegation;
use Omeka\Permissions\Assertion\HasSitePermissionAssertion;
use Omeka\Permissions\Assertion\SiteIsPublicAssertion;
use Omeka\Permissions\Assertion\IsSelfAssertion;
use Omeka\Permissions\Assertion\OwnsEntityAssertion;
use Omeka\Permissions\Assertion\UserIsAdminAssertion;
use Zend\Permissions\Acl\Assertion\AssertionAggregate;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 * Access control list factory.
 */
class AclFactory implements FactoryInterface
{
    /**
     * Create the access control list.
     *
     * @param ContainerInterface $serviceLocator
     * @return Acl
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $acl = new Acl;

        $auth = $serviceLocator->get('Omeka\AuthenticationService');
        $acl->setAuthenticationService($auth);

        $this->addRoles($acl);
        $this->addResources($acl, $serviceLocator);

        $status = $serviceLocator->get('Omeka\Status');
        if (!$status->isInstalled()
            || ($status->needsVersionUpdate() && $status->needsMigration())
        ) {
            // Allow all privileges during installation and migration.
            $acl->allow();
        } else {
            $this->addRules($acl);
        }

        return $acl;
    }

    /**
     * Add ACL roles.
     *
     * @param Acl $acl
     */
    protected function addRoles(Acl $acl)
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
     * @param ContainerInterface $serviceLocator
     */
    protected function addResources(Acl $acl, ContainerInterface $serviceLocator)
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
        $controllers = array_merge(
            array_keys($config['controllers']['invokables']),
            array_keys($config['controllers']['factories'])
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
        $this->addRulesForSites($acl);
        $this->addRulesForAllRoles($acl);
        $this->addRulesForResearcher($acl);
        $this->addRulesForAuthor($acl);
        $this->addRulesForReviewer($acl);
        $this->addRulesForEditor($acl);
        $this->addRulesForSiteAdmin($acl);
        $this->addRulesForGlobalAdmin($acl);
    }

    /**
     * Add rules for sites.
     *
     * @param Acl $acl
     */
    protected function addRulesForSites(Acl $acl)
    {
        $acl->allow(
            null,
            [
                'Omeka\Controller\SiteAdmin\Index',
                'Omeka\Controller\SiteAdmin\Page',
                'Omeka\Controller\Site\Index',
                'Omeka\Controller\Site\Item',
                'Omeka\Controller\Site\ItemSet',
                'Omeka\Controller\Site\Media',
                'Omeka\Controller\Site\Page',
            ]
        );
        $acl->allow(
            null,
            [
                'Omeka\Api\Adapter\SiteAdapter',
                'Omeka\Api\Adapter\SitePageAdapter',
            ]
        );

        $acl->allow(
            'editor',
            'Omeka\Entity\Site',
            'create'
        );

        $acl->allow(
            null,
            'Omeka\Entity\SitePage',
            'create'
        );

        $acl->allow(
            null,
            'Omeka\Entity\Site',
            'delete',
            new OwnsEntityAssertion
        );

        $adminAssertion = $this->aggregate([
            new OwnsEntityAssertion,
            new HasSitePermissionAssertion('admin'),
        ], AssertionAggregate::MODE_AT_LEAST_ONE);
        $acl->allow(
            null,
            'Omeka\Entity\Site',
            'update',
            $adminAssertion
        );

        $editorAssertion = $this->aggregate([
                new OwnsEntityAssertion,
                new HasSitePermissionAssertion('editor'),
            ], AssertionAggregate::MODE_AT_LEAST_ONE);
        $acl->allow(
            null,
            'Omeka\Entity\Site',
            'add-page',
            $editorAssertion
        );
        $acl->allow(
            null,
            'Omeka\Entity\SitePage',
            ['update', 'delete'],
            $editorAssertion
        );

        $viewerAssertion = $this->aggregate([
            new SiteIsPublicAssertion,
            new OwnsEntityAssertion,
            new HasSitePermissionAssertion('viewer'),
        ], AssertionAggregate::MODE_AT_LEAST_ONE);
        $acl->allow(
            null,
            ['Omeka\Entity\Site', 'Omeka\Entity\SitePage'],
            'read',
            $viewerAssertion
        );
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
            [
                'Omeka\Controller\Api',
                'Omeka\Controller\Index',
                'Omeka\Controller\Login',
                'Omeka\Controller\Maintenance',
                'Omeka\Controller\Migrate',
            ]
        );
        $acl->allow(
            null,
            [
                'Omeka\Api\Adapter\ItemSetAdapter',
                'Omeka\Api\Adapter\ItemAdapter',
                'Omeka\Api\Adapter\MediaAdapter',
                'Omeka\Api\Adapter\VocabularyAdapter',
                'Omeka\Api\Adapter\ResourceClassAdapter',
                'Omeka\Api\Adapter\PropertyAdapter',
                'Omeka\Api\Adapter\ResourceTemplateAdapter',
                'Omeka\Api\Adapter\AssetAdapter',
                'Omeka\Api\Adapter\ApiResourceAdapter',
            ],
            [
                'search',
                'read',
            ]
        );
        $acl->allow(
            null,
            [
                'Omeka\Api\Adapter\ResourceAdapter',
                'Omeka\Entity\ItemSet',
                'Omeka\Entity\Item',
                'Omeka\Entity\Media',
                'Omeka\Entity\Vocabulary',
                'Omeka\Entity\ResourceClass',
                'Omeka\Entity\Property',
                'Omeka\Entity\ResourceTemplate',
                'Omeka\Entity\Asset',
            ],
            [
                'read',
            ]
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
            [
                'Omeka\Controller\Admin\Index',
                'Omeka\Controller\Admin\Item',
                'Omeka\Controller\Admin\ItemSet',
                'Omeka\Controller\Admin\Media',
                'Omeka\Controller\Admin\ResourceTemplate',
                'Omeka\Controller\Admin\Vocabulary',
                'Omeka\Controller\Admin\ResourceClass',
                'Omeka\Controller\Admin\Property',
                'Omeka\Controller\Admin\SystemInfo',
            ],
            [
                'index',
                'browse',
                'show',
                'show-details',
            ]
        );
        $acl->allow(
            'researcher',
            [
                'Omeka\Controller\Admin\Item',
                'Omeka\Controller\Admin\ItemSet',
            ],
            ['sidebar-select', 'search']
        );
        $acl->allow(
            'researcher',
            'Omeka\Controller\Admin\Asset',
            'sidebar-select'
        );
        $acl->allow(
            'researcher',
            'Omeka\Controller\Admin\Vocabulary',
            ['classes', 'properties']
        );
        $acl->allow(
            'researcher',
            'Omeka\Controller\Admin\User'
        );
        $acl->allow(
            'researcher',
            'Omeka\Api\Adapter\UserAdapter',
            ['read', 'update', 'search']
        );
        $acl->allow(
            'researcher',
            'Omeka\Entity\User',
            'read'
        );
        $acl->allow(
            'researcher',
            'Omeka\Entity\User',
            ['update', 'change-password', 'edit-keys'],
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
            [
                'Omeka\Controller\Admin\Index',
                'Omeka\Controller\Admin\Item',
                'Omeka\Controller\Admin\ItemSet',
                'Omeka\Controller\Admin\Media',
                'Omeka\Controller\Admin\ResourceTemplate',
                'Omeka\Controller\Admin\Vocabulary',
                'Omeka\Controller\Admin\ResourceClass',
                'Omeka\Controller\Admin\Property',
                'Omeka\Controller\Admin\SystemInfo',
            ],
            [
                'index',
                'browse',
                'show',
                'show-details',
            ]
        );
        $acl->allow(
            'author',
            'Omeka\Controller\Admin\ResourceTemplate',
            ['add-new-property-row', 'import']
        );
        $acl->allow(
            'author',
            [
                'Omeka\Controller\Admin\Item',
                'Omeka\Controller\Admin\ItemSet',
            ],
            ['sidebar-select', 'search']
        );
        $acl->allow(
            null,
            'Omeka\Controller\Admin\Asset',
            'sidebar-select'
        );
        $acl->allow(
            'author',
            'Omeka\Controller\Admin\Vocabulary',
            ['classes', 'properties']
        );
        $acl->allow(
            'author',
            [
                'Omeka\Controller\Admin\Item',
                'Omeka\Controller\Admin\ItemSet',
                'Omeka\Controller\Admin\Media',
                'Omeka\Controller\Admin\ResourceTemplate',
            ],
            [
                'add',
                'edit',
                'delete',
                'delete-confirm',
            ]
        );
        $acl->allow(
            'author',
            [
                'Omeka\Api\Adapter\ItemAdapter',
                'Omeka\Api\Adapter\ItemSetAdapter',
                'Omeka\Api\Adapter\MediaAdapter',
                'Omeka\Api\Adapter\ResourceTemplateAdapter',
            ],
            [
                'create',
                'update',
                'delete',
            ]
        );
        $acl->allow(
            'author',
            [
                'Omeka\Entity\Item',
                'Omeka\Entity\ItemSet',
                'Omeka\Entity\Media',
                'Omeka\Entity\ResourceTemplate',
            ],
            [
                'create',
            ]
        );
        $acl->allow(
            'author',
            [
                'Omeka\Entity\Item',
                'Omeka\Entity\ItemSet',
                'Omeka\Entity\Media',
                'Omeka\Entity\ResourceTemplate',
            ],
            [
                'update',
                'delete',
            ],
            new OwnsEntityAssertion
        );
        $acl->allow(
            'author',
            'Omeka\Controller\Admin\User'
        );
        $acl->allow(
            'author',
            'Omeka\Api\Adapter\UserAdapter',
            ['read', 'update', 'search']
        );
        $acl->allow(
            'author',
            'Omeka\Entity\User',
            'read'
        );
        $acl->allow(
            'author',
            'Omeka\Entity\User',
            ['update', 'change-password', 'edit-keys'],
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
            [
                'Omeka\Controller\Admin\Index',
                'Omeka\Controller\Admin\Item',
                'Omeka\Controller\Admin\ItemSet',
                'Omeka\Controller\Admin\Media',
                'Omeka\Controller\Admin\ResourceTemplate',
                'Omeka\Controller\Admin\Vocabulary',
                'Omeka\Controller\Admin\ResourceClass',
                'Omeka\Controller\Admin\Property',
                'Omeka\Controller\Admin\SystemInfo',
            ],
            [
                'index',
                'browse',
                'show',
                'show-details',
            ]
        );
        $acl->allow(
            'reviewer',
            [
                'Omeka\Controller\Admin\Item',
                'Omeka\Controller\Admin\ItemSet',
            ],
            ['sidebar-select', 'search']
        );
        $acl->allow(
            null,
            'Omeka\Controller\Admin\Asset',
            'sidebar-select'
        );
        $acl->allow(
            'reviewer',
            'Omeka\Controller\Admin\Vocabulary',
            ['classes', 'properties']
        );
        $acl->allow(
            'reviewer',
            [
                'Omeka\Controller\Admin\Item',
                'Omeka\Controller\Admin\ItemSet',
                'Omeka\Controller\Admin\Media',
            ],
            [
                'add',
                'edit',
                'delete',
                'delete-confirm',
            ]
        );
        $acl->allow(
            'reviewer',
            [
                'Omeka\Api\Adapter\ItemAdapter',
                'Omeka\Api\Adapter\ItemSetAdapter',
                'Omeka\Api\Adapter\MediaAdapter',
            ],
            [
                'create',
                'update',
                'delete',
            ]
        );
        $acl->allow(
            'reviewer',
            [
                'Omeka\Entity\Item',
                'Omeka\Entity\ItemSet',
                'Omeka\Entity\Media',
            ],
            [
                'create',
                'update',
            ]
        );
        $acl->allow(
            'reviewer',
            [
                'Omeka\Entity\Item',
                'Omeka\Entity\ItemSet',
                'Omeka\Entity\Media',
            ],
            [
                'delete',
            ],
            new OwnsEntityAssertion
        );
        $acl->allow(
            'reviewer',
            'Omeka\Entity\Resource',
            'view-all'
        );
        $acl->allow(
            'reviewer',
            'Omeka\Controller\Admin\User'
        );
        $acl->allow(
            'reviewer',
            'Omeka\Api\Adapter\UserAdapter',
            ['read', 'update', 'search']
        );
        $acl->allow(
            'reviewer',
            'Omeka\Entity\User',
            'read'
        );
        $acl->allow(
            'reviewer',
            'Omeka\Entity\User',
            ['update', 'change-password', 'edit-keys'],
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
            [
                'Omeka\Controller\Admin\Index',
                'Omeka\Controller\Admin\Item',
                'Omeka\Controller\Admin\ItemSet',
                'Omeka\Controller\Admin\Media',
                'Omeka\Controller\Admin\ResourceTemplate',
                'Omeka\Controller\Admin\Vocabulary',
                'Omeka\Controller\Admin\ResourceClass',
                'Omeka\Controller\Admin\Property',
                'Omeka\Controller\Admin\SystemInfo',
            ],
            [
                'index',
                'browse',
                'show',
                'show-details',
            ]
        );
        $acl->allow(
            'editor',
            'Omeka\Controller\Admin\ResourceTemplate',
            ['add-new-property-row', 'import']
        );
        $acl->allow(
            'editor',
            [
                'Omeka\Controller\Admin\Item',
                'Omeka\Controller\Admin\ItemSet',
            ],
            ['sidebar-select', 'search']
        );
        $acl->allow(
            null,
            'Omeka\Controller\Admin\Asset',
            'sidebar-select'
        );
        $acl->allow(
            'editor',
            'Omeka\Controller\Admin\Vocabulary',
            ['classes', 'properties']
        );
        $acl->allow(
            'editor',
            [
                'Omeka\Controller\Admin\Item',
                'Omeka\Controller\Admin\ItemSet',
                'Omeka\Controller\Admin\Media',
                'Omeka\Controller\Admin\ResourceTemplate',
            ],
            [
                'add',
                'edit',
                'delete',
                'delete-confirm',
            ]
        );
        $acl->allow(
            'editor',
            [
                'Omeka\Api\Adapter\ItemAdapter',
                'Omeka\Api\Adapter\ItemSetAdapter',
                'Omeka\Api\Adapter\MediaAdapter',
                'Omeka\Api\Adapter\ResourceTemplateAdapter',
            ],
            [
                'create',
                'update',
                'delete',
            ]
        );
        $acl->allow(
            'editor',
            [
                'Omeka\Entity\Item',
                'Omeka\Entity\ItemSet',
                'Omeka\Entity\Media',
                'Omeka\Entity\ResourceTemplate',
            ],
            [
                'create',
                'update',
                'delete',
            ]
        );
        $acl->allow(
            'editor',
            'Omeka\Entity\Resource',
            'view-all'
        );
        $acl->allow(
            'editor',
            'Omeka\Controller\Admin\User'
        );
        $acl->allow(
            'editor',
            'Omeka\Api\Adapter\UserAdapter'
        );
        $acl->allow(
            'editor',
            'Omeka\Entity\User',
            ['read']
        );
        $acl->allow(
            'editor',
            'Omeka\Entity\User',
            ['update', 'change-password', 'edit-keys'],
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
            ['Omeka\Module\Manager', 'Omeka\Controller\Admin\Module'],
            ['activate', 'deactivate', 'install', 'uninstall', 'upgrade', 'configure']
        );
        $acl->deny(
            'site_admin',
            'Omeka\Controller\Admin\Vocabulary',
            ['import']
        );
        $acl->deny(
            'site_admin',
            'Omeka\Controller\Admin\Setting'
        );
        $acl->deny(
            'site_admin',
            'Omeka\Api\Adapter\VocabularyAdapter',
            ['create', 'update', 'delete']
        );

        $acl->deny(
            'site_admin',
            'Omeka\Entity\User',
            'change-role-admin'
        );
        $acl->deny(
            'site_admin',
            'Omeka\Entity\User',
            ['change-role', 'activate-user', 'delete'],
            new IsSelfAssertion
        );

        // Site admins should not be able to edit other admin users but should
        // be able to edit themselves
        $denyEdit = new AssertionAggregate;
        $denyEdit->addAssertions([
            new UserIsAdminAssertion,
            new AssertionNegation(new IsSelfAssertion),
        ]);
        $acl->deny(
            'site_admin',
            'Omeka\Entity\User',
            ['update', 'delete', 'change-password', 'edit-keys'],
            $denyEdit
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
        $acl->deny(
            'global_admin',
            'Omeka\Entity\User',
            ['change-role', 'activate-user', 'delete'],
            new IsSelfAssertion
        );
    }

    /**
     * Create an assertion aggregate from the given assertions and the given mode.
     *
     * This is just a convenience method to avoid having to write the somewhat verbose aggregate
     * code over and over within the grants.
     *
     * @param array $assertions Array of Assertion objects to be aggregated
     * @param string $mode Aggregation mode
     * @return AssertionAggregate
     */
    protected function aggregate(array $assertions, $mode = AssertionAggregate::MODE_ALL)
    {
        $aggregate = new AssertionAggregate;
        $aggregate->addAssertions($assertions);
        $aggregate->setMode($mode);
        return $aggregate;
    }
}

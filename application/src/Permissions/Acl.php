<?php
namespace Omeka\Permissions;

use Zend\Authentication\AuthenticationServiceInterface;
use Zend\Permissions\Acl\Acl as ZendAcl;

class Acl extends ZendAcl
{
    const ROLE_GLOBAL_ADMIN = 'global_admin';
    const ROLE_SITE_ADMIN   = 'site_admin';
    const ROLE_EDITOR       = 'editor';
    const ROLE_REVIEWER     = 'reviewer';
    const ROLE_AUTHOR       = 'author';
    const ROLE_RESEARCHER   = 'researcher';

    /**
     * @var array
     */
    protected $roleLabels = [
        self::ROLE_GLOBAL_ADMIN => 'Global Administrator',
        self::ROLE_SITE_ADMIN   => 'Site Administrator',
        self::ROLE_EDITOR       => 'Editor',
        self::ROLE_REVIEWER     => 'Reviewer',
        self::ROLE_AUTHOR       => 'Author',
        self::ROLE_RESEARCHER   => 'Researcher',
    ];

    /**
     * Roles that are "admins" and restricted for editing.
     *
     * @var array
     */
    protected $adminRoles = [
        self::ROLE_GLOBAL_ADMIN,
        self::ROLE_SITE_ADMIN,
    ];

    /**
     * @var AuthenticationServiceInterface
     */
    protected $auth;

    /**
     * {@inheritDoc}
     */
    public function setAuthenticationService(AuthenticationServiceInterface $auth)
    {
        $this->auth = $auth;
    }

    /**
     * {@inheritDoc}
     */
    public function getAuthenticationService()
    {
        return $this->auth;
    }

    /**
     * Get role names and their labels.
     *
     * @param bool $excludeAdminRoles Whether to only return the non-admin
     *  roles. False by default, so all roles are returned.
     * @return array
     */
    public function getRoleLabels($excludeAdminRoles = false)
    {
        $labels = $this->roleLabels;

        if ($excludeAdminRoles) {
            foreach ($this->adminRoles as $role) {
                unset($labels[$role]);
            }
        }
        return $labels;
    }

    /**
     * Authorize the current user.
     *
     * @param Resource\ResourceInterface|string $resource
     * @param string $privilege
     * @return bool
     */
    public function userIsAllowed($resource = null, $privilege = null)
    {
        $auth = $this->auth;
        $role = null;
        if ($auth) {
            $role = $auth->getIdentity();
        }
        return $this->isAllowed($role, $resource, $privilege);
    }

    /**
     * Determine whether the admin role is an "admin" role that carries
     * restrictions beyond other roles.
     *
     * @return bool
     */
    public function isAdminRole($role)
    {
        return in_array($role, $this->adminRoles);
    }
}

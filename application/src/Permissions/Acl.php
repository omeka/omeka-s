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
    protected $roleLabels = array(
        self::ROLE_GLOBAL_ADMIN => 'Global Administrator',
        self::ROLE_SITE_ADMIN   => 'Site Administrator',
        self::ROLE_EDITOR       => 'Editor',
        self::ROLE_REVIEWER     => 'Reviewer',
        self::ROLE_AUTHOR       => 'Author',
        self::ROLE_RESEARCHER   => 'Researcher',
    );

    /**
     * Roles that are "admins" and restricted for editing.
     *
     * @var array
     */
    protected $adminRoles = array(
        self::ROLE_GLOBAL_ADMIN,
        self::ROLE_SITE_ADMIN,
    );

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
     * @return array
     */
    public function getRoleLabels()
    {
        return $this->roleLabels;
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
     * @return boolean
     */
    public function isAdminRole($role)
    {
        return in_array($role, $this->adminRoles);
    }
}

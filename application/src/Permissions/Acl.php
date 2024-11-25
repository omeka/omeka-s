<?php
namespace Omeka\Permissions;

use Omeka\Api\ResourceInterface;
use Laminas\Authentication\AuthenticationServiceInterface;
use Laminas\Permissions\Acl\Acl as LaminasAcl;

class Acl extends LaminasAcl
{
    const ROLE_GLOBAL_ADMIN = 'global_admin';
    const ROLE_SITE_ADMIN = 'site_admin';
    const ROLE_EDITOR = 'editor';
    const ROLE_REVIEWER = 'reviewer';
    const ROLE_AUTHOR = 'author';
    const ROLE_RESEARCHER = 'researcher';

    /**
     * @var AuthenticationServiceInterface
     */
    protected $auth;

    /**
     * @var array
     */
    protected $configAcl;

    public function setAuthenticationService(AuthenticationServiceInterface $auth)
    {
        $this->auth = $auth;
    }

    public function getAuthenticationService(): AuthenticationServiceInterface
    {
        return $this->auth;
    }

    public function setConfigAcl(array $configAcl)
    {
        $this->configAcl = $configAcl;
    }

    /**
     * Get role names and their labels.
     *
     * @param bool $excludeAdminRoles Whether to only return the non-admin
     *  roles. False by default, so all roles are returned.
     */
    public function getRoleLabels($excludeAdminRoles = false): array
    {
        return $excludeAdminRoles
            ? array_diff_key($this->configAcl['labels'], $this->configAcl['admin_roles'])
            : $this->configAcl['labels'];
    }

    /**
     * Authorize the current user.
     *
     * @param ResourceInterface|string $resource
     * @param string $privilege
     */
    public function userIsAllowed($resource = null, $privilege = null): bool
    {
        $role = $this->auth
            ? $this->auth->getIdentity()
            : null;
        return $this->isAllowed($role, $resource, $privilege);
    }

    /**
     * Determine whether the admin role is an "admin" role that carries
     * restrictions beyond other roles.
     */
    public function isAdminRole($role): bool
    {
        return in_array($role, $this->configAcl['admin_roles']);
    }

    /**
     * Add a role label to the ACL
     *
     * @param string $roleId
     * @param string $roleLabel
     *
     * @deprecated Use main config acl labels.
     */
    public function addRoleLabel($roleId, $roleLabel)
    {
        $this->configAcl['labels'][$roleId] = $roleLabel;
    }

    /**
     * Remove a role label from the ACL
     *
     * @param $roleId
     *
     * @deprecated Will be removed in a future version.
     * @todo Check the purpose of this method that is never used.
     */
    public function removeRoleLabel($roleId)
    {
        unset($this->configAcl['labels'][$roleId]);
    }
}

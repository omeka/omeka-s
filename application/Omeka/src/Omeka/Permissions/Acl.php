<?php
namespace Omeka\Permissions;

use Zend\Authentication\AuthenticationServiceInterface;
use Zend\Permissions\Acl\Acl as ZendAcl;

class Acl extends ZendAcl
{
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

    public function userIsAllowed($resource = null, $privilege = null)
    {
        $auth = $this->auth;
        $role = $auth->getIdentity();
        if (!$role) {
            $role = 'guest';
        }
        return $this->isAllowed($role, $resource, $privilege);
    }
}

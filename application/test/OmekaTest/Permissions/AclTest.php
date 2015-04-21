<?php
namespace OmekaTest\Permissions;

use Omeka\Permissions\Acl;
use Omeka\Test\TestCase;

class AclTest extends TestCase
{
    protected $acl;

    public function setUp()
    {
        $this->acl = new Acl;
        $this->acl->addRole('guest');
        $this->acl->addRole('not-guest');
        $this->acl->addResource('resource');

        $this->acl->allow('not-guest', 'resource', 'not-guest-priv');
        $this->acl->allow('guest', 'resource', 'guest-priv');
    }

    public function testUserIsAllowedWithNoAuth()
    {
        $this->assertFalse($this->acl->userIsAllowed('resource', 'not-guest-priv'));
    }

    public function testUserIsAllowedWithNoUser()
    {
        $auth = $this->getMockForAbstractClass('Zend\Authentication\AuthenticationServiceInterface');
        $auth->expects($this->any())
             ->method('getIdentity')
             ->will($this->returnValue(null));
        $this->acl->setAuthenticationService($auth);

        $this->assertFalse($this->acl->userIsAllowed('resource', 'not-guest-priv'));
    }

    public function testUserIsAllowedWithUser()
    {
        $user = $this->getMockForAbstractClass('Zend\Permissions\Acl\Role\RoleInterface');
        $user->expects($this->any())
             ->method('getRoleId')
             ->will($this->returnValue('not-guest'));
        $auth = $this->getMockForAbstractClass('Zend\Authentication\AuthenticationServiceInterface');
        $auth->expects($this->any())
             ->method('getIdentity')
             ->will($this->returnValue($user));
        $this->acl->setAuthenticationService($auth);

        $this->assertTrue($this->acl->userIsAllowed('resource', 'not-guest-priv'));
        $this->assertFalse($this->acl->userIsAllowed('resource', 'guest-priv'));
    }

    public function testSetGetAuthService()
    {
        $auth = $this->getMockForAbstractClass('Zend\Authentication\AuthenticationServiceInterface');
        $this->acl->setAuthenticationService($auth);
        $this->assertSame($auth, $this->acl->getAuthenticationService());
    }
}

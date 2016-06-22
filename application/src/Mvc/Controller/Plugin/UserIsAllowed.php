<?php
namespace Omeka\Mvc\Controller\Plugin;

use Omeka\Permissions\Acl;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class UserIsAllowed extends AbstractPlugin
{
    /**
     * @var Acl
     */
    protected $acl;

    public function __construct(Acl $acl)
    {
        $this->acl = $acl;
    }

    /**
     * Authorize the current user.
     *
     * @param Resource\ResourceInterface|string $resource
     * @param string $privilege
     * @return bool
     */
    public function __invoke($resource = null, $privilege = null)
    {
        return $this->acl->userIsAllowed($resource, $privilege);
    }
}

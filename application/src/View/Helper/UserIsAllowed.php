<?php
namespace Omeka\View\Helper;

use Omeka\Permissions\Acl;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Helper\AbstractHelper;

class UserIsAllowed extends AbstractHelper
{
    /**
     * @var Acl
     */
    protected $acl;

    /**
     * Construct the helper.
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
        $this->acl = $serviceLocator->get('Omeka\Acl');
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

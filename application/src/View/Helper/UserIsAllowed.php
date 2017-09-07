<?php
namespace Omeka\View\Helper;

use Omeka\Permissions\Acl;
use Zend\View\Helper\AbstractHelper;

/**
 * View helper for authorizing the current user.
 */
class UserIsAllowed extends AbstractHelper
{
    /**
     * @var Acl
     */
    protected $acl;

    /**
     * Construct the helper.
     *
     * @param Acl $acl
     */
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

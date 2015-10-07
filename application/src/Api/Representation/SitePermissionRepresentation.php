<?php
namespace Omeka\Api\Representation;

use Omeka\Entity\SitePermission;
use Zend\ServiceManager\ServiceLocatorInterface;

class SitePermissionRepresentation extends AbstractRepresentation
{
    /**
     * @var SitePermission
     */
    protected $permission;

    /**
     * Construct the site permission representation object.
     *
     * @param SitePermission $permission
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct(SitePermission $permission, ServiceLocatorInterface $serviceLocator)
    {
        $this->setServiceLocator($serviceLocator);
        $this->permission = $permission;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return [
            'o:user' => $this->user()->getReference(),
            'o:role' => $this->role(),

        ];
    }

    /**
     * @return SiteRepresentation
     */
    public function site()
    {
        return $this->getAdapter('sites')
            ->getRepresentation($this->permission->getSite());
    }

    /**
     * @return UserRepresentation
     */
    public function user()
    {
        return $this->getAdapter('users')
            ->getRepresentation($this->permission->getUser());
    }

    /**
     * @return string
     */
    public function role()
    {
        return $this->permission->getRole();
    }
}

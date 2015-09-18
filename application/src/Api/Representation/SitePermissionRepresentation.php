<?php
namespace Omeka\Api\Representation;

use Omeka\Api\Exception;
use Omeka\Entity\SitePermission;
use Zend\ServiceManager\ServiceLocatorInterface;

class SitePermissionRepresentation extends AbstractRepresentation
{
    /**
     * Construct the site permission representation object.
     *
     * @param mixed $data
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct($data, ServiceLocatorInterface $serviceLocator)
    {
        // Set the service locator first.
        $this->setServiceLocator($serviceLocator);
        $this->setData($data);
    }

    /**
     * @var array
     */
    public function validateData($data)
    {
        if (!$data instanceof SitePermission) {
            throw new Exception\InvalidArgumentException(
                $this->getTranslator()->translate(sprintf(
                    'Invalid data sent to %s.', get_called_class()
                ))
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return array(
            'o:user' => $this->user()->getReference(),
            'o:role' => $this->role(),

        );
    }

    /**
     * @return SiteRepresentation
     */
    public function site()
    {
        return $this->getAdapter('sites')
            ->getRepresentation(null, $this->getData()->getSite());
    }

    /**
     * @return UserRepresentation
     */
    public function user()
    {
        return $this->getAdapter('users')
            ->getRepresentation(null, $this->getData()->getUser());
    }

    /**
     * @return string
     */
    public function role()
    {
        return $this->getData()->getRole();
    }
}

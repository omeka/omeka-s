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
        $sitePermission = $this->getData();
        return array(
            'o:user' => $this->getReference(
                null,
                $sitePermission->getUser(),
                $this->getAdapter('users')
            ),
            'o:admin' => $sitePermission->getAdmin(),
            'o:attach' => $sitePermission->getAttach(),
            'o:edit' => $sitePermission->getEdit(),
        );
    }
}

<?php
namespace Omeka\Api\Representation;

use Omeka\Api\Exception;
use Omeka\Entity\ResourceTemplateProperty;
use Zend\ServiceManager\ServiceLocatorInterface;

class ResourceTemplatePropertyRepresentation extends AbstractRepresentation
{
    /**
     * Construct the resource template property representation object.
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
     * {@inheritDoc}
     */
    public function validateData($data)
    {
        if (!$data instanceof ResourceTemplateProperty) {
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
            'o:property' => $this->property()->getReference(),
            'o:alternate_label' => $this->alternateLabel(),
            'o:alternate_comment' => $this->alternateComment(),
        );
    }

    /**
     * @return ResourceTemplateRepresentation
     */
    public function site()
    {
        return $this->getAdapter('resource_templates')
            ->getRepresentation(null, $this->getData()->getResourceTemplate());
    }

    /**
     * @return PropertyRepresentation
     */
    public function property()
    {
        return $this->getAdapter('properties')
            ->getRepresentation(null, $this->getData()->getProperty());
    }

    /**
     * @return string
     */
    public function alternateLabel()
    {
        return $this->getData()->getAlternateLabel();
    }

    /**
     * @return string
     */
    public function alternateComment()
    {
        return $this->getData()->getAlternateComment();
    }

    /**
     * @return int
     */
    public function position()
    {
        return $this->getData()->getPosition();
    }
}

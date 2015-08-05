<?php
namespace Omeka\Api\Representation;

use Omeka\Api\Exception;
use Omeka\Entity\SitePageBlock;
use Zend\ServiceManager\ServiceLocatorInterface;

class SitePageBlockRepresentation extends AbstractRepresentation
{
    /**
     * Construct the block object.
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
        if (!$data instanceof SitePageBlock) {
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
            'o:layout' => $this->layout(),
            'o:data' => $this->data(),
        );
    }

    /**
     * @return SiteRepresentation
     */
    public function page()
    {
        return $this->getAdapter('site_pages')
            ->getRepresentation(null, $this->getData()->getPage());
    }

    /**
     * @return bool
     */
    public function layout()
    {
        return $this->getData()->getLayout();
    }

    /**
     * @return bool
     */
    public function data()
    {
        return $this->getData()->getData();
    }
}

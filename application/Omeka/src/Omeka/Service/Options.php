<?php
namespace Omeka\Service;

use Doctrine\ORM\EntityManager;
use Omeka\Model\Entity\Option;
use Zend\Json\Json;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class Options implements ServiceLocatorAwareInterface
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * Set an option.
     *
     * Will overwrite an existing option with the same ID.
     *
     * @param string $id
     * @param string value
     */
    public function set($id, $value)
    {
        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');
        $option = $this->findEntity($id, $entityManager);
        if (!$option instanceof Option) {
            $option = new Option;
        }
        $option->setId($id);
        $option->setValue(Json::encode($value));
        $entityManager->persist($option);
        $entityManager->flush();
    }

    /**
     * Get an option.
     *
     * Will return false if no option exists with the passed ID.
     *
     * @param string $id
     * @return mixed
     */
    public function get($id)
    {
        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');
        $option = $this->findEntity($id, $entityManager);
        if (!$option instanceof Option) {
            return false;
        }
        return Json::decode($option->getValue(), Json::TYPE_ARRAY);
    }

    /**
     * Delete an option.
     *
     * @param string $id
     */
    public function delete($id)
    {
        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');
        $option = $this->findEntity($id, $entityManager);
        if ($option instanceof Option) {
            $entityManager->remove($option);
            $entityManager->flush();
        }
    }

    /**
     * Find an option entity.
     *
     * @param string $id
     * @param EntityManager $entityManager
     * @return Object|null
     */
    protected function findEntity($id, EntityManager $entityManager)
    {
        return $entityManager
            ->getRepository('Omeka\Model\Entity\Option')
            ->findOneById($id);
    }

    /**
     * {@inheritDoc}
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->services = $serviceLocator;
    }

    /**
     * {@inheritDoc}
     */
    public function getServiceLocator()
    {
        return $this->services;
    }
}

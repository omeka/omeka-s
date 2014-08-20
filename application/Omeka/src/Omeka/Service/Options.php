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
     * @var array Options cache
     */
    protected $options;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * Set an option
     *
     * This will overwrite an existing option with the same ID. A null value
     * will delete an existing option.
     *
     * @param string $id
     * @param string $value
     */
    public function set($id, $value)
    {
        if (null === $value) {
            // Null value deletes option
            $this->delete($id);
            return;
        }

        if (null === $this->options) {
            // Cache options if not already cached
            $this->cacheOptions();
        }

        if ($this->exists($id) && $value === $this->options[$id]) {
            // An equal option already set, do nothing
            return;
        }

        // Set option to cache
        if (is_object($value)) {
            // When fetching options from the database, Doctrine decodes from
            // JSON and converts objects to associative arrays. Below simulates
            // Doctrine's roundtrip format of an object and sets it as the
            // cached value.
            $this->options[$id] = json_decode(json_encode($value), true);
        } else {
            $this->options[$id] = $value;
        }

        // Set option to database
        $option = $this->findOption($id);
        if ($option instanceof Option) {
            $option->setValue($value);
        } else {
            $option = new Option;
            $option->setId($id);
            $option->setValue($value);
            $this->getEntityManager()->persist($option);
        }
        $this->getEntityManager()->flush();
    }

    /**
     * Get an option
     *
     * Will return null if no option exists with the passed ID.
     *
     * @param string $id
     * @return mixed
     */
    public function get($id, $default = null)
    {
        if (null === $this->options) {
            // Cache options if not already cached
            $this->cacheOptions();
        }

        if (!$this->exists($id)) {
            // Option does not exist, return default
            return $default;
        }

        return $this->options[$id];
    }

    /**
     * Delete an option
     *
     * @param string $id
     */
    public function delete($id)
    {
        if (null === $this->options) {
            // Cache options if not already cached
            $this->cacheOptions();
        }

        if (!$this->exists($id)) {
            // Option does not exist, do nothing
            return;
        }

        // Delete option from cache
        unset($this->options[$id]);

        // Delete option from database
        $option = $this->findOption($id);
        if ($option instanceof Option) {
            $this->getEntityManager()->remove($option);
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Check whether an option already exists
     *
     * @param string $id
     * @return bool
     */
    public function exists($id)
    {
        return array_key_exists($id, $this->options);
    }

    /**
     * Cache options
     */
    protected function cacheOptions()
    {
        $rows = $this->getEntityManager()
            ->getRepository('Omeka\Model\Entity\Option')
            ->findAll();
        $this->options = array();
        foreach ($rows as $row) {
            $this->options[$row->getId()] = $row->getValue();
        }
    }

    /**
     * Find an option entity
     *
     * @param string $id
     * @return Option|null
     */
    protected function findOption($id)
    {
        return $this->getEntityManager()
            ->getRepository('Omeka\Model\Entity\Option')
            ->findOneById($id);
    }

    /**
     * Get the entity manager
     *
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        if (null === $this->entityManager) {
            $this->entityManager = $this->getServiceLocator()
                ->get('Omeka\EntityManager');
        }
        return $this->entityManager;
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

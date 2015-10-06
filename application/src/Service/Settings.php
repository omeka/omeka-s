<?php
namespace Omeka\Service;

use Doctrine\ORM\EntityManager;
use Omeka\Entity\Setting;
use Zend\Json\Json;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class Settings implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * @var array Settings cache
     */
    protected $settings;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * Set a setting
     *
     * This will overwrite an existing setting with the same ID. A null value
     * will delete an existing setting.
     *
     * @param string $id
     * @param string $value
     */
    public function set($id, $value)
    {
        if (null === $value) {
            // Null value deletes setting
            $this->delete($id);
            return;
        }

        if (null === $this->settings) {
            // Cache settings if not already cached
            $this->cacheSettings();
        }

        if ($this->exists($id) && $value === $this->settings[$id]) {
            // An equal setting already set, do nothing
            return;
        }

        // Set setting to cache
        if (is_object($value)) {
            // When fetching settings from the database, Doctrine decodes from
            // JSON and converts objects to associative arrays. Below simulates
            // Doctrine's roundtrip format of an object and sets it as the
            // cached value.
            $this->settings[$id] = json_decode(json_encode($value), true);
        } else {
            $this->settings[$id] = $value;
        }

        // Set setting to database
        $setting = $this->findSetting($id);
        if ($setting instanceof Setting) {
            $setting->setValue($value);
        } else {
            $setting = new Setting;
            $setting->setId($id);
            $setting->setValue($value);
            $this->getEntityManager()->persist($setting);
        }
        $this->getEntityManager()->flush();
    }

    /**
     * Get a setting
     *
     * Will return null if no setting exists with the passed ID.
     *
     * @param string $id
     * @return mixed
     */
    public function get($id, $default = null)
    {
        if (null === $this->settings) {
            // Cache settings if not already cached
            $this->cacheSettings();
        }

        if (!$this->exists($id)) {
            // Setting does not exist, return default
            return $default;
        }

        return $this->settings[$id];
    }

    /**
     * Delete a setting
     *
     * @param string $id
     */
    public function delete($id)
    {
        if (null === $this->settings) {
            // Cache settings if not already cached
            $this->cacheSettings();
        }

        if (!$this->exists($id)) {
            // Setting does not exist, do nothing
            return;
        }

        // Delete setting from cache
        unset($this->settings[$id]);

        // Delete setting from database
        $setting = $this->findSetting($id);
        if ($setting instanceof Setting) {
            $this->getEntityManager()->remove($setting);
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Check whether a setting already exists
     *
     * @param string $id
     * @return bool
     */
    public function exists($id)
    {
        return array_key_exists($id, $this->settings);
    }

    /**
     * Cache settings if Omeka is installed
     */
    protected function cacheSettings()
    {
        $this->settings = [];
        if ($this->getServiceLocator()->get('Omeka\Status')->isInstalled()) {
            $rows = $this->getEntityManager()
                ->getRepository('Omeka\Entity\Setting')
                ->findAll();
            foreach ($rows as $row) {
                $this->settings[$row->getId()] = $row->getValue();
            }
        }
    }

    /**
     * Find a setting entity
     *
     * @param string $id
     * @return Setting|null
     */
    protected function findSetting($id)
    {
        return $this->getEntityManager()
            ->getRepository('Omeka\Entity\Setting')
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
}

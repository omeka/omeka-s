<?php
namespace Omeka\Settings;

use Doctrine\DBAL\Connection;
use Omeka\Mvc\Status;

abstract class AbstractSettings implements SettingsInterface
{
    /**
     * @var array
     */
    protected $cache;

    /**
     * @var Doctrine\DBAL\Connection
     */
    protected $connection;

    /**
     * @var Status
     */
    protected $status;

    /**
     * Cache all settings from a data store.
     */
    abstract protected function setCache();

    /**
     * Set a setting to a data store.
     *
     * @param string $id
     * @param mixed $value
     */
    abstract protected function setSetting($id, $value);

    /**
     * Delete a setting from a data store.
     *
     * @param string $id
     * @param mixed $value
     */
    abstract protected function deleteSetting($id);

    public function __construct(Connection $connection, Status $status)
    {
        $this->connection = $connection;
        $this->status = $status;
    }

    /**
     * Set a setting
     *
     * This will overwrite an existing setting with the same ID. A null value
     * will delete an existing setting.
     *
     * @param string $id
     * @param mixed $value
     */
    public function set($id, $value)
    {
        if (null === $value) {
            // Null value deletes setting
            $this->delete($id);
            return;
        }

        if (null === $this->cache) {
            // Cache settings if not already cached
            $this->cache();
        }

        if ($this->isCached($id) && $value === $this->cache[$id]) {
            // An equal setting already set, do nothing
            return;
        }

        // Set setting to cache
        if (is_object($value)) {
            // When fetching settings from the database, Doctrine decodes from
            // JSON and converts objects to associative arrays. Below simulates
            // Doctrine's roundtrip format of an object and sets it as the
            // cached value.
            $this->cache[$id] = json_decode(json_encode($value), true);
        } else {
            $this->cache[$id] = $value;
        }

        $this->setSetting($id, $value);
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
        if (null === $this->cache) {
            // Cache settings if not already cached
            $this->cache();
        }

        if (!$this->isCached($id)) {
            // Setting does not exist, return default
            return $default;
        }

        return $this->cache[$id];
    }

    /**
     * Delete a setting
     *
     * @param string $id
     */
    public function delete($id)
    {
        if (null === $this->cache) {
            // Cache settings if not already cached
            $this->cache();
        }

        if (!$this->isCached($id)) {
            // Setting does not exist, do nothing
            return;
        }

        // Delete setting from cache
        unset($this->cache[$id]);

        $this->deleteSetting($id);
    }

    /**
     * Is a setting already cached?
     *
     * @param string $id
     * @return bool
     */
    protected function isCached($id)
    {
        return array_key_exists($id, $this->cache);
    }

    /**
     * Cache all settings.
     */
    protected function cache()
    {
        $this->cache = [];
        if (!$this->status->isInstalled()) {
            return;
        }
        $this->setCache();
    }

    /**
     * Get the DBAL connection
     *
     * @return Doctrine\DBAL\Connection
     */
    protected function getConnection()
    {
        return $this->connection;
    }
}

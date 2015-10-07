<?php
namespace Omeka\Module;

use Omeka\Api\ResourceInterface;

/**
 * A module registered in the module manager.
 */
class Module implements ResourceInterface
{
    /** @var string */
    protected $id;

    /** @var string */
    protected $state;

    /** @var array */
    protected $ini;

    /** @var array */
    protected $db;

    /**
     * Construct the module.
     *
     * @param string $id The module identifier, the directory name
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * Get the module identifier.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the module state.
     *
     * @param string $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * Get the module state.
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set the module INI data.
     *
     * @param array $ini
     */
    public function setIni($ini)
    {
        $this->ini = $ini;
    }

    /**
     * Get the module INI data, the entire array or by key.
     *
     * @param string $key
     * @return array|string|null
     */
    public function getIni($key = null)
    {
        if ($key) {
            return isset($this->ini[$key]) ? $this->ini[$key] : null;
        }
        return $this->ini;
    }

    /**
     * Set the module database data.
     *
     * @param array $db
     */
    public function setDb($db)
    {
        $this->db = $db;
    }

    /**
     * Get the module database data, the entire array or by key.
     *
     * @param string $key
     * @return array|string|null
     */
    public function getDb($key = null)
    {
        if ($key) {
            return isset($this->db[$key]) ? $this->db[$key] : null;
        }
        return $this->db;
    }

    /**
     * Check whether this module is configurable
     *
     * @return bool
     */
    public function isConfigurable()
    {
        return (bool) $this->getIni('configurable');
    }

    /**
     * Get the name of this module.
     *
     * @return string|null
     */
    public function getName()
    {
        if ($name = $this->getIni('name')) {
            return $name;
        }
        if ($name = $this->getDb('id')) {
            return $name;
        }
        if ($name = $this->getId()) {
            return $name;
        }
        // Could not find a name.
        return null;
    }
}

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

    /** @var string */
    protected $moduleFilePath;

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
            return $this->ini[$key] ?? null;
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
            return $this->db[$key] ?? null;
        }
        return $this->db;
    }

    /**
     * Set the path to the module's Module.php file.
     *
     * @param string $path
     */
    public function setModuleFilePath($path)
    {
        $this->moduleFilePath = $path;
    }

    /**
     * Get the path to the module's Module.php file.
     *
     * @return string
     */
    public function getModuleFilePath()
    {
        return $this->moduleFilePath;
    }

    /**
     * Check whether this module is configurable.
     *
     * When the key "configurable" is set in module.ini, its value is used.
     * Otherwise, auto-detection checks the form ConfigForm or the content of
     * method getConfigForm() via reflection.
     */
    public function isConfigurable(): bool
    {
        $configurable = $this->getIni('configurable');
        if ($configurable !== null) {
            return (bool) $configurable;
        }

        $moduleFilePath = $this->getModuleFilePath();
        if (!$moduleFilePath) {
            return false;
        }

        // Check ConfigForm.
        $moduleDir = dirname($moduleFilePath);
        if (file_exists($moduleDir . '/src/Form/ConfigForm.php')) {
            return true;
        }

        // Check method in module via Reflection.
        $moduleClass = $this->getId() . '\Module';
        if (class_exists($moduleClass, false)) {
            try {
                $ref = new \ReflectionMethod($moduleClass, 'getConfigForm');
                return $ref->getFileName() === realpath($moduleFilePath);
            } catch (\ReflectionException $e) {
                return false;
            }
        }

        // Check method in module via file when not active.
        if (file_exists($moduleFilePath)) {
            $source = file_get_contents($moduleFilePath);
            return (bool) preg_match('/function\s+getConfigForm\s*\(/', $source);
        }

        return false;
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

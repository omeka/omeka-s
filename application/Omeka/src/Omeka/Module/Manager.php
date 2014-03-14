<?php
namespace Omeka\Module;

class Manager
{
    const STATE_ACTIVE        = 'active';
    const STATE_NOT_ACTIVE    = 'not_active';
    const STATE_NOT_INSTALLED = 'not_installed';
    const STATE_NOT_FOUND     = 'not_found';

    /**
     * @var array Valid module states
     */
    protected $validStates = array(
        self::STATE_ACTIVE,
        self::STATE_NOT_ACTIVE,
        self::STATE_NOT_INSTALLED,
        self::STATE_NOT_FOUND,
    );

    /**
     * @var array All found module IDs and their info
     */
    protected $foundModules = array();

    /**
     * @var array Module IDs assigned to their current state
     */
    protected $moduleStates = array(
        // Modules that are found, installed, and active
        self::STATE_ACTIVE        => array(),
        // Modules that are found, installed, and not active
        self::STATE_NOT_ACTIVE    => array(),
        // Modules that are in the filesystem but not in the database
        self::STATE_NOT_INSTALLED => array(),
        // Modules that are in the database but not in the filesystem. Modules
        // in this state do not have a corresponding found module.
        self::STATE_NOT_FOUND     => array(),
    );

    /**
     * Set a found module ID and its info (from config/module.ini)
     *
     * @param string $id The module ID
     * @param array $info The module info
     */
    public function setFound($id, array $info)
    {
        $this->foundModules[$id] = $info;
    }

    /**
     * Get found module info
     *
     * @param string $id The module ID
     * @return array|null The module info
     */
    public function getFound($id = null)
    {
        if (null !== $id && !$this->isFound($id)) {
            throw new \InvalidArgumentException(sprintf('Invalid module ID: %s', $id));
        }
        return null === $id ? $this->foundModules : $this->foundModules[$id];
    }

    /**
     * Completely remove a found module
     *
     * @param string $id The module ID
     */
    public function removeFound($id)
    {
        if (!$this->isFound($id)) {
            throw new \InvalidArgumentException(sprintf('Invalid module ID: %s', $id));
        }
        $this->removeFromState($id);
        unset($this->foundModules[$id]);
    }

    /**
     * Check whether the module is found
     *
     * @param string $id The module ID
     * @return bool
     */
    public function isFound($id)
    {
        return isset($this->foundModules[$id]);
    }

    /**
     * Set the module state
     *
     * @param string $id The module ID
     * @param string $state The module state
     */
    public function setToState($id, $state)
    {
        if (!$this->isFound($id)) {
            throw new \InvalidArgumentException(sprintf('Invalid module ID: %s', $id));
        }
        if (!$this->isValidState($state)) {
            throw new \InvalidArgumentException(sprintf('Invalid module state: %s', $state));
        }
        $this->removeFromState($id);
        $this->moduleStates[$state][] = $id;
    }

    /**
     * Get all module IDs from a specific state
     *
     * @param string $state The module state
     * @return array
     */
    public function getFromState($state)
    {
        if (!$this->isValidState($state)) {
            throw new \InvalidArgumentException(sprintf('Invalid module state: %s', $state));
        }
        return $this->moduleStates[$state];
    }

    /**
     * Remove the module from state
     *
     * @param string $id The module ID
     */
    public function removeFromState($id)
    {
        if (!$this->isFound($id)) {
            throw new \InvalidArgumentException(sprintf('Invalid module ID: %s', $id));
        }
        // Iterate all states just to be sure
        foreach ($this->validStates as $state) {
            unset($this->moduleStates[$state][$id]);
        }
    }

    /**
     * Check whether the module is set to a state
     *
     * @param string $id The module ID
     * @param string|null $state The module state
     * @return bool
     */
    public function isInState($id, $state = null)
    {
        if (!$this->isFound($id)) {
            throw new \InvalidArgumentException(sprintf('Invalid module ID: %s', $id));
        }
        if (null !== $state && !$this->isValidState($state)) {
            throw new \InvalidArgumentException(sprintf('Invalid module state: %s', $state));
        }
        // Check the specified state
        if (null !== $state) {
            return in_array($id, $this->moduleStates[$state]) ? true : false;
        }
        // Check all states
        foreach ($this->moduleStates as $ids) {
            if (in_array($id, $ids)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check whether the state is valid
     *
     * @param string $state The module state
     * @return bool
     */
    public function isValidState($state)
    {
        return in_array($state, $this->validStates)
    }
}

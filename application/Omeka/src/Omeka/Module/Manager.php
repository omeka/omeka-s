<?php
namespace Omeka\Module;

use Doctrine\ORM\EntityManager;
use Omeka\Event\Event;
use Omeka\Model\Entity\Module;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class Manager implements ServiceLocatorAwareInterface
{
    const STATE_ACTIVE         = 'active';
    const STATE_NOT_ACTIVE     = 'not_active';
    const STATE_NOT_INSTALLED  = 'not_installed';
    const STATE_NOT_FOUND      = 'not_found';
    const STATE_INVALID_MODULE = 'invalid_module';
    const STATE_INVALID_INI    = 'invalid_ini';
    const STATE_NEEDS_UPGRADE  = 'needs_upgrade';

    /**
     * @var array Valid module states
     */
    protected $validStates = array(
        // A module that is valid, installed, and active
        self::STATE_ACTIVE,
        // A module that is valid, installed, and not active
        self::STATE_NOT_ACTIVE,
        // A module that is in the filesystem but not in the database
        self::STATE_NOT_INSTALLED,
        // A module that is in the database but not in the filesystem
        self::STATE_NOT_FOUND,
        // A module with an invalid Module.php file
        self::STATE_INVALID_MODULE,
        // A module with an invalid config/module.ini file
        self::STATE_INVALID_INI,
        // A module where the filesystem version is newer than the installed version
        self::STATE_NEEDS_UPGRADE,
    );

    /**
     * @var array Registered modules
     */
    protected $modules = array();

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * Register a new module
     *
     * @param string $id
     */
    public function registerModule($id)
    {
        $this->modules[$id] = array(
            'state' => null,
            'ini'   => null,
            'db'    => null,
        );
    }

    /**
     * Set a module's state
     *
     * @param string $id
     * @param string $state
     */
    public function setModuleState($id, $state)
    {
        $this->moduleIsRegistered($id, true);
        if (!in_array($state, $this->validStates)) {
            throw new Exception\ModuleStateInvalidException(sprintf(
                'Attempting to set an invalid module state "%s"', $state
            ));
        }
        $this->modules[$id]['state'] = $state;
    }

    /**
     * Set a module's INI
     *
     * @param string $id
     * @param array $ini
     */
    public function setModuleIni($id, array $ini)
    {
        $this->moduleIsRegistered($id, true);
        $this->modules[$id]['ini'] = $ini;
    }

    /**
     * Set a module's db row
     *
     * @param string $id
     * @param array $row
     */
    public function setModuleDb($id, array $row)
    {
        $this->moduleIsRegistered($id, true);
        $this->modules[$id]['db'] = $row;
    }

    /**
     * Check whether a module is registered
     *
     * @throws Exception\ModuleNotRegisteredException
     * @param string $id
     * @param bool $throwException Throw exception when not registered
     * @return bool
     */
    public function moduleIsRegistered($id, $throwException = false)
    {
        $isRegistered = array_key_exists($id, $this->modules);
        if ($throwException && !$isRegistered) {
            throw new Exception\ModuleNotRegisteredException(sprintf(
                'Module "%s" is not registered', $id
            ));
        }
        return $isRegistered;
    }

    /**
     * Check whether a module has state
     *
     * @param string $id
     * @return bool
     */
    public function moduleHasState($id)
    {
        $this->moduleIsRegistered($id, true);
        return (bool) $this->modules[$id]['state'];
    }

    /**
     * Check whether a module is configurable
     *
     * @param string $id
     * @return bool
     */
    public function moduleIsConfigurable($id)
    {
        $this->moduleIsRegistered($id, true);
        $ini = $this->modules[$id]['ini'];
        return isset($ini['configurable']) && (bool) $ini['configurable'];
    }

    /**
     * Get all modules
     *
     * @return array
     */
    public function getModules()
    {
        return $this->modules;
    }

    /**
     * Get all modules by state
     *
     * @param string $state
     * @return array
     */
    public function getModulesByState($state)
    {
        $modules = array();
        foreach ($this->modules as $id => $module) {
            if ($state == $module['state']) {
                $modules[$id] = $module;
            }
        }
        return $modules;
    }

    /**
     * Get a module
     *
     * @param string $id
     * @return array
     */
    public function getModule($id)
    {
        $this->moduleIsRegistered($id, true);
        return $this->modules[$id];
    }

    /**
     * Get a module's state
     *
     * @param string $id
     * @return string
     */
    public function getModuleState($id)
    {
        $this->moduleIsRegistered($id, true);
        return $this->modules[$id]['state'];
    }

    /**
     * Get a module's INI
     *
     * @param string $id
     * @return array|null
     */
    public function getModuleIni($id)
    {
        $this->moduleIsRegistered($id, true);
        return $this->modules[$id]['ini'];
    }

    /**
     * Get a module's db row
     *
     * @param string $id
     * @return array|null
     */
    public function getModuleDb($id)
    {
        $this->moduleIsRegistered($id, true);
        return $this->modules[$id]['db'];
    }

    /**
     * Check whether the INI is valid
     *
     * @param array $ini
     */
    public function moduleIniIsValid(array $ini)
    {
        if (!isset($ini['name'])) {
            return false;
        }
        if (!isset($ini['version'])) {
            return false;
        }
        return true;
    }

    /**
     * Activate a module
     *
     * @param string $id
     */
    public function activate($id)
    {
        $this->moduleIsRegistered($id, true);

        // Only a deactivated module can be activated
        if (self::STATE_NOT_ACTIVE !== $this->getModuleState($id)) {
            throw new Exception\ModuleStateInvalidException(sprintf(
                'Module "%s" is marked as "%s" and cannot be activated',
                $id, $this->getModuleState($id)
            ));
        }

        $module = $this->findModule($id);
        if ($module instanceof Module) {
            $module->setIsActive(true);
            $this->getEntityManager()->flush();
        } else {
            throw new Exception\ModuleNotInDatabaseException(sprintf(
                'Module "%s" not in database during activation', $id
            ));
        }

        $this->setModuleState($id, self::STATE_ACTIVE);
    }

    /**
     * Deactivate a module
     *
     * @param string $id
     */
    public function deactivate($id)
    {
        $this->moduleIsRegistered($id, true);

        // Only an active module can be deactivated
        if (self::STATE_ACTIVE !== $this->getModuleState($id)) {
            throw new Exception\ModuleStateInvalidException(sprintf(
                'Module "%s" is marked as "%s" and cannot be deactivated',
                $id, $this->getModuleState($id)
            ));
        }

        $module = $this->findModule($id);
        if ($module instanceof Module) {
            $module->setIsActive(false);
            $this->getEntityManager()->flush();
        } else {
            throw new Exception\ModuleNotInDatabaseException(sprintf(
                'Module "%s" not in database during deactivation', $id
            ));
        }

        $this->setModuleState($id, self::STATE_NOT_ACTIVE);
    }

    /**
     * Install and activate a module
     *
     * @param string $id
     */
    public function install($id)
    {
        $this->moduleIsRegistered($id, true);

        // Only a not installed module can be installed
        if (self::STATE_NOT_INSTALLED !== $this->getModuleState($id)) {
            throw new Exception\ModuleStateInvalidException(sprintf(
                'Module "%s" is marked as "%s" and cannot be installed',
                $id, $this->getModuleState($id)
            ));
        }

        // Invoke the module's install method
        $this->getModuleObject($id)->install(
            $this->getServiceLocator()
        );

        // Persist the module entity
        $module = new Module;
        $module->setId($id);
        $module->setIsActive(true);
        $module->setVersion($this->modules[$id]['ini']['version']);

        $this->getEntityManager()->persist($module);
        $this->getEntityManager()->flush();

        $this->setModuleState($id, self::STATE_ACTIVE);
    }

    /**
     * Uninstall a module
     *
     * @param string $id
     */
    public function uninstall($id)
    {
        $this->moduleIsRegistered($id, true);

        // Only an installed and upgraded module can be uninstalled
        if (!in_array($this->getModuleState($id), array(
            self::STATE_ACTIVE,
            self::STATE_NOT_ACTIVE,
        ))) {
            throw new Exception\ModuleStateInvalidException(sprintf(
                'Module "%s" is marked as "%s" and cannot be uninstalled',
                $id, $this->getModuleState($id)
            ));
        }

        // Invoke the module's uninstall method
        $this->getModuleObject($id)->uninstall(
            $this->getServiceLocator()
        );

        // Remove the module entity
        $module = $this->findModule($id);
        if ($module instanceof Module) {
            $this->getEntityManager()->remove($module);
            $this->getEntityManager()->flush();
        } else {
            throw new Exception\ModuleNotInDatabaseException(sprintf(
                'Module "%s" not found in database during uninstallation', $id
            ));
        }

        $this->setModuleState($id, self::STATE_NOT_INSTALLED);
    }

    /**
     * Upgrade a module
     *
     * @param string $id
     */
    public function upgrade($id)
    {
        $this->moduleIsRegistered($id, true);

        // Only a module marked for upgrade can be upgraded
        if (self::STATE_NEEDS_UPGRADE !== $this->getModuleState($id)) {
            throw new Exception\ModuleStateInvalidException(sprintf(
                'Module "%s" is marked as "%s" and cannot be upgraded',
                $id, $this->getModuleState($id)
            ));
        }

        $oldVersion = $this->modules[$id]['db']['version'];
        $newVersion = $this->modules[$id]['ini']['version'];

        // Invoke the module's upgrade method
        $this->getModuleObject($id)->upgrade(
            $oldVersion,
            $newVersion,
            $this->getServiceLocator()
        );

        // Update the module entity
        $module = $this->findModule($id);
        if ($module instanceof Module) {
            $module->setVersion($newVersion);
            $this->getEntityManager()->flush();
        } else {
            throw new Exception\ModuleNotInDatabaseException(sprintf(
                'Module "%s" not found in database during upgrade', $id
            ));
        }

        $this->setModuleState($id, $this->modules[$id]['db']['is_active']
            ? self::STATE_ACTIVE : self::STATE_NOT_ACTIVE);
    }

    /**
     * Get a module object
     *
     * Get from Zend's module manager if loaded (i.e. active), otherwise
     * instantiate a new module object.
     *
     * @param string $id
     * @param string $methodName
     */
    protected function getModuleObject($id)
    {
        $this->moduleIsRegistered($id, true);
        $module = $this->getServiceLocator()
            ->get('ModuleManager')->getModule($id);
        if (null !== $module) {
            return $module;
        }
        $moduleClass = "$id\Module";
        return new $moduleClass;
    }

    /**
     * Find a module entity
     *
     * @param string $id
     * @return Module|null
     */
    protected function findModule($id)
    {
        return $this->getEntityManager()
            ->getRepository('Omeka\Model\Entity\Module')
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

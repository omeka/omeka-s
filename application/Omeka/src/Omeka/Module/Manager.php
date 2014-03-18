<?php
namespace Omeka\Module;

use Omeka\Event\Event;
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
    );

    /**
     * @var array
     */
    protected $modules = array();

    /**
     * Set a new module to the list
     *
     * @param string $id
     */
    public function setModule($id)
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
        $this->modules[$id]['db'] = $row;
    }

    /**
     * Check whether a module exists
     *
     * @param string $id
     * @return bool
     */
    public function moduleExists($id)
    {
        return array_key_exists($id, $this->modules);
    }

    /**
     * Check whether a module has state
     *
     * @param string $id
     * @return bool
     */
    public function moduleHasState($id)
    {
        return (bool) $this->modules[$id]['state'];
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
     * Check whether the INI is valid
     *
     * @param array $ini
     */
    public function moduleIniIsValid(array $ini)
    {
        if (!isset($ini['version'])) {
            return false;
        }
        return true;
    }

    /**
     * Install a module
     *
     * @param string $id
     */
    public function install($id)
    {
        // Trigger the module.install event
        $this->triggerModuleEvent($id, Event::EVENT_MODULE_INSTALL);
    }

    /**
     * Uninstall a module
     *
     * @param string $id
     */
    public function uninstall($id)
    {
        // Trigger the module.uninstall event
        $this->triggerModuleEvent($id, Event::EVENT_MODULE_UNINSTALL);
    }

    /**
     * Activate a module
     *
     * @param string $id
     */
    public function activate($id)
    {
        // Trigger the module.activate event
        $this->triggerModuleEvent($id, Event::EVENT_MODULE_ACTIVATE);
    }

    /**
     * Deactivate a module
     *
     * @param string $id
     */
    public function deactivate($id)
    {
        // Trigger the module.deactivate event
        $this->triggerModuleEvent($id, Event::EVENT_MODULE_DEACTIVATE);
    }

    /**
     * Upgrade a module
     *
     * @param string $id
     */
    public function upgrade($id)
    {
        // Trigger the module.upgrade event
        $this->triggerModuleEvent($id, Event::EVENT_MODULE_UPGRADE);
    }

    /**
     * Trigger a module event
     *
     * @param string $id
     * @param string $eventName
     */
    protected function triggerModuleEvent($id, $eventName)
    {
        $event = new Event($eventName, $this, array(
            'services' => $this->getServiceLocator(),
        ));
        $this->getServiceLocator()->get('ModuleManager')
            ->getModule($id)->getEventManager()->trigger($event);
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

<?php
namespace Omeka\Module;

use Doctrine\ORM\EntityManager;
use Omeka\Entity\Module as ModuleEntity;
use Omeka\Permissions\Exception as AclException;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\Permissions\Acl\Resource\ResourceInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class Manager implements ResourceInterface
{
    const STATE_ACTIVE = 'active';
    const STATE_NOT_ACTIVE = 'not_active';
    const STATE_NOT_INSTALLED = 'not_installed';
    const STATE_NOT_FOUND = 'not_found';
    const STATE_INVALID_MODULE = 'invalid_module';
    const STATE_INVALID_INI = 'invalid_ini';
    const STATE_INVALID_OMEKA_VERSION = 'invalid_omeka_version';
    const STATE_NEEDS_UPGRADE = 'needs_upgrade';

    /**
     * @var array Valid module states
     */
    protected $validStates = [
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
        // A module with an Omeka version constraint that doesn't match the current version
        self::STATE_INVALID_OMEKA_VERSION,
        // A module where the filesystem version is newer than the installed version
        self::STATE_NEEDS_UPGRADE,
    ];

    /**
     * @var array Registered modules
     */
    protected $modules = [];

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * Register a new module
     *
     * @param string $id
     * @return Module
     */
    public function registerModule($id)
    {
        $module = new Module($id);
        $this->modules[$id] = $module;
        return $module;
    }

    /**
     * Check whether the module INI is valid
     *
     * @param Module $module
     * @return bool
     */
    public function iniIsValid(Module $module)
    {
        $ini = $module->getIni();
        if (!isset($ini['name'])) {
            return false;
        }
        if (!isset($ini['version'])) {
            return false;
        }
        return true;
    }

    /**
     * Check whether a module is registered
     *
     * @param string $id
     * @return bool
     */
    public function isRegistered($id)
    {
        return array_key_exists($id, $this->modules);
    }

    /**
     * Get a registered module
     *
     * @param string $id
     * @return Module|false Returns false when id is invalid
     */
    public function getModule($id)
    {
        return $this->isRegistered($id) ? $this->modules[$id] : false;
    }

    /**
     * Get all registered modules
     *
     * @return array
     */
    public function getModules()
    {
        return $this->modules;
    }

    /**
     * Get all registered modules by state
     *
     * @param string $state
     * @return array
     */
    public function getModulesByState($state)
    {
        $modules = [];
        foreach ($this->modules as $id => $module) {
            if ($state == $module->getState()) {
                $modules[$id] = $module;
            }
        }
        return $modules;
    }

    /**
     * Activate a module
     *
     * @param Module $module
     */
    public function activate(Module $module)
    {
        $this->authorize($module, 'activate');
        $t = $this->getTranslator();

        // Only a deactivated module can be activated
        if (self::STATE_NOT_ACTIVE !== $module->getState()) {
            throw new Exception\ModuleStateInvalidException(sprintf(
                $t->translate('Module "%s" is marked as "%s" and cannot be activated'),
                $module->getId(), $module->getState()
            ));
        }

        $entity = $this->getModuleEntity($module);
        if ($entity instanceof ModuleEntity) {
            $entity->setIsActive(true);
            $this->getEntityManager()->flush();
        } else {
            throw new Exception\ModuleNotInDatabaseException(sprintf(
                $t->translate('Module "%s" not in database during activation'),
                $module->getId()
            ));
        }

        $module->setState(self::STATE_ACTIVE);
    }

    /**
     * Deactivate a module
     *
     * @param Module $module
     */
    public function deactivate(Module $module)
    {
        $this->authorize($module, 'deactivate');
        $t = $this->getTranslator();

        // Only an active module can be deactivated
        if (self::STATE_ACTIVE !== $module->getState()) {
            throw new Exception\ModuleStateInvalidException(sprintf(
                $t->translate('Module "%s" is marked as "%s" and cannot be deactivated'),
                $module->getId(), $module->getState()
            ));
        }

        $entity = $this->getModuleEntity($module);
        if ($entity instanceof ModuleEntity) {
            $entity->setIsActive(false);
            $this->getEntityManager()->flush();
        } else {
            throw new Exception\ModuleNotInDatabaseException(sprintf(
                $t->translate('Module "%s" not in database during deactivation'),
                $module->getId()
            ));
        }

        $module->setState(self::STATE_NOT_ACTIVE);
    }

    /**
     * Install and activate a module
     *
     * @param Module $module
     */
    public function install(Module $module)
    {
        $this->authorize($module, 'install');

        // Only a not installed module can be installed
        if (self::STATE_NOT_INSTALLED !== $module->getState()) {
            throw new Exception\ModuleStateInvalidException(sprintf(
                $this->getTranslator()->translate('Module "%s" is marked as "%s" and cannot be installed'),
                $module->getId(), $module->getState()
            ));
        }

        // Invoke the module's install method
        $this->getModuleObject($module)->install(
            $this->serviceLocator
        );

        // Persist the module entity
        $entity = new ModuleEntity;
        $entity->setId($module->getId());
        $entity->setIsActive(true);
        $entity->setVersion($module->getIni('version'));

        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();

        $module->setState(self::STATE_ACTIVE);
    }

    /**
     * Uninstall a module
     *
     * @param Module $module
     */
    public function uninstall(Module $module)
    {
        $this->authorize($module, 'uninstall');
        $t = $this->getTranslator();

        // Only an installed and upgraded module can be uninstalled
        if (!in_array($module->getState(), [
            self::STATE_ACTIVE,
            self::STATE_NOT_ACTIVE,
        ])) {
            throw new Exception\ModuleStateInvalidException(sprintf(
                $t->translate('Module "%s" is marked as "%s" and cannot be uninstalled'),
                $module->getId(), $module->getState()
            ));
        }

        // Invoke the module's uninstall method
        $this->getModuleObject($module)->uninstall(
            $this->serviceLocator
        );

        // Remove the module entity
        $entity = $this->getModuleEntity($module);
        if ($entity instanceof ModuleEntity) {
            $this->getEntityManager()->remove($entity);
            $this->getEntityManager()->flush();
        } else {
            throw new Exception\ModuleNotInDatabaseException(sprintf(
                $t->translate('Module "%s" not found in database during uninstallation'),
                $module->getId()
            ));
        }

        $module->setState(self::STATE_NOT_INSTALLED);
    }

    /**
     * Upgrade a module
     *
     * @param Module $module
     */
    public function upgrade(Module $module)
    {
        $this->authorize($module, 'upgrade');
        $t = $this->getTranslator();

        // Only a module marked for upgrade can be upgraded
        if (self::STATE_NEEDS_UPGRADE !== $module->getState()) {
            throw new Exception\ModuleStateInvalidException(sprintf(
                $t->translate('Module "%s" is marked as "%s" and cannot be upgraded'),
                $module->getId(), $module->getState()
            ));
        }

        $oldVersion = $module->getDb('version');
        $newVersion = $module->getIni('version');

        // Invoke the module's upgrade method
        $this->getModuleObject($module)->upgrade(
            $oldVersion,
            $newVersion,
            $this->serviceLocator
        );

        // Update the module entity
        $entity = $this->getModuleEntity($module);
        if ($entity instanceof ModuleEntity) {
            $entity->setVersion($newVersion);
            $this->getEntityManager()->flush();
        } else {
            throw new Exception\ModuleNotInDatabaseException(sprintf(
                $t->translate('Module "%s" not found in database during upgrade'),
                $module->getId()
            ));
        }

        $module->setState($module->getDb('is_active')
            ? self::STATE_ACTIVE : self::STATE_NOT_ACTIVE);
    }

    /**
     * Get a module entity
     *
     * @param Module $module
     * @return ModuleEntity|null
     */
    protected function getModuleEntity(Module $module)
    {
        return $this->getEntityManager()
            ->getRepository('Omeka\Entity\Module')
            ->findOneById($module->getId());
    }

    /**
     * Get a module object
     *
     * Get from Zend's module manager if loaded (i.e. active), otherwise
     * instantiate a new module object.
     *
     * @param Module $module
     */
    protected function getModuleObject(Module $module)
    {
        $moduleObject = $this->serviceLocator->get('ModuleManager')->getModule($module->getId());
        if (null !== $moduleObject) {
            return $moduleObject;
        }
        $moduleClass = $module->getId() . "\Module";
        return new $moduleClass;
    }

    /**
     * Get the entity manager
     *
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        if (null === $this->entityManager) {
            $this->entityManager = $this->serviceLocator->get('Omeka\EntityManager');
        }
        return $this->entityManager;
    }

    /**
     * Get the translator service
     *
     * return TranslatorInterface
     */
    public function getTranslator()
    {
        if (!$this->translator instanceof TranslatorInterface) {
            $this->translator = $this->serviceLocator->get('MvcTranslator');
        }
        return $this->translator;
    }

    /**
     * Verify that the current user has permission.
     *
     * @throws AclException\PermissionDeniedException
     * @param Module $module
     * @param string $privilege
     */
    protected function authorize(Module $module, $privilege)
    {
        $acl = $this->serviceLocator->get('Omeka\Acl');
        if (!$acl->userIsAllowed($this, $privilege)) {
            throw new AclException\PermissionDeniedException(sprintf(
                $this->getTranslator()->translate(
                    'Permission denied for the current user to %s the %s module.'
                ),
                $privilege, $module->getId()
            ));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getResourceId()
    {
        return get_called_class();
    }
}

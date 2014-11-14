<?php
namespace Omeka\View\Helper;

use Omeka\Module\Manager as ModuleManager;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Helper\AbstractHtmlElement;

class AssetUrl extends AbstractHtmlElement
{
    const OMEKA_ASSETS_PATH = '%s/application/Omeka/asset/%s';
    const MODULE_ASSETS_PATH = '%s/module/%s/asset/%s';

    /**
     * @var ModuleManager
     */
    protected $moduleManager;

    /**
     * @var array Cached array of all active modules
     */
    protected $activeModules;

    /**
     * Construct the helper.
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->moduleManager = $serviceLocator->get('Omeka\ModuleManager');
        $this->activeModules = $this->moduleManager
            ->getModulesByState(ModuleManager::STATE_ACTIVE);
    }

    /**
     * Return a path to an asset by module directory name.
     *
     * The module must be active. Does not check if the asset file exists.
     *
     * @param string $file
     * @param string $module
     * @return string|null
     */
    public function __invoke($file, $module)
    {
        $basePath = $this->getView()->basePath();
        if ('Omeka' == $module) {
            return sprintf(self::OMEKA_ASSETS_PATH, $basePath, $file);
        }
        if (array_key_exists($module, $this->activeModules)) {
            return sprintf(self::MODULE_ASSETS_PATH, $basePath, $module, $file);
        }
    }
}

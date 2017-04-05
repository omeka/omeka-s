<?php
namespace Omeka\Module;

use ReflectionClass;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\Mvc\Controller\AbstractController;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Renderer\PhpRenderer;

/**
 * Abstract Omeka module.
 */
abstract class AbstractModule implements ConfigProviderInterface
{
    use EventManagerAwareTrait;

    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

    /**
     * Bootstrap the module.
     *
     * Call parent::onBootstrap($event) first when overriding this method.
     *
     * @param MvcEvent $event
     */
    public function onBootstrap(MvcEvent $event)
    {
        $this->setServiceLocator($event->getApplication()->getServiceManager());
        $this->attachListeners($this->getServiceLocator()->get('SharedEventManager'));
    }

    /**
     * Install this module.
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function install(ServiceLocatorInterface $serviceLocator)
    {
    }

    /**
     * Uninstall this module.
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
    }

    /**
     * Upgrade this module.
     *
     * @param string $oldVersion
     * @param string $newVersion
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function upgrade($oldVersion, $newVersion,
        ServiceLocatorInterface $serviceLocator
    ) {
    }

    /**
     * Get this module's configuration form.
     *
     * @param PhpRenderer $renderer
     * @return string
     */
    public function getConfigForm(PhpRenderer $renderer)
    {
    }

    /**
     * Handle this module's configuration form.
     *
     * @param AbstractController $controller
     * @return bool False if there was an error during handling
     */
    public function handleConfigForm(AbstractController $controller)
    {
    }

    /**
     * Attach listeners to the $sharedEventManager for shared events:
     *
     * <code>
     * $sharedEventManager->attach(
     *     'Omeka\Identifier',
     *     'shared_event_name',
     *     array($this, 'mySharedEventCallback')
     * );
     * </code>
     *
     * The shared event callbacks receive a
     * {@link \Zend\EventManager\EventInterface} object as its only parameter.
     *
     * @param SharedEventManagerInterface $sharedEventManager
     */
    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
    }

    /**
     * Return module-specific configuration.
     *
     * {@inheritDoc}
     */
    public function getConfig()
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getAutoloaderConfig()
    {
        $classInfo = new ReflectionClass($this);
        $namespace = $classInfo->getNamespaceName();

        // Omeka is already registered via Composer.
        if ('Omeka' == $namespace) {
            return;
        }

        $autoloadPath = sprintf('%1$s/modules/%2$s/src', OMEKA_PATH, $namespace);
        return [
            'Zend\Loader\StandardAutoloader' => [
                'namespaces' => [
                    $namespace => $autoloadPath,
                ],
            ],
        ];
    }

    /**
     * Set the service locator.
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * Get the service locator.
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }
}

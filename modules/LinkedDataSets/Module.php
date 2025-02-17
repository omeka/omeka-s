<?php

declare(strict_types=1);

namespace LinkedDataSets;

use Generic\AbstractModule as GenericModule;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use LinkedDataSets\Application\Job\DataDumpJob;
use LinkedDataSets\Application\Job\RecreateDataCatalogsJob;
use Omeka\Entity\Item;
use Omeka\Job\Dispatcher;
use Omeka\Job\DispatchStrategy\Synchronous;
use Omeka\Module\Exception\ModuleCannotInstallException;
use Omeka\Module\Module as DefaultModule;
use Omeka\Stdlib\Message;

// see https://gitlab.com/Daniel-KM/Omeka-S-module-Generic
if (!class_exists(\Generic\AbstractModule::class)) {
    require file_exists(dirname(__DIR__) . '/Generic/AbstractModule.php')
        ? dirname(__DIR__) . '/Generic/AbstractModule.php'
        : __DIR__ . '/src/Generic/AbstractModule.php';
}

final class Module extends GenericModule
{
    const NAMESPACE = __NAMESPACE__;
    private ?Dispatcher $dispatcher = null;
    private $api = null;
    private array $config;

    public function __construct()
    {
        $this->config = $this->getConfig();
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    public function install(ServiceLocatorInterface $serviceLocator): void
    {
        $this->checkPrerequisites($serviceLocator);
        $this->createFoldersIfTheyDontExist();
        parent::install($serviceLocator);
    }

    protected function checkPrerequisites(ServiceLocatorInterface $serviceLocator): void
    {
        $modules = $this->config['dependencies']['modules'];
        /* @var DefaultModule $module */
        foreach ($this->config['dependencies']['modules'] as $moduleDependency) {
            $module = $serviceLocator->get('Omeka\ModuleManager')
                ->getModule($moduleDependency['name'])
            ;

            if ($module && version_compare($module->getIni('version') ?? '', $moduleDependency['version'], '<')) {
                $translator = $serviceLocator->get('MvcTranslator');
                $message = new Message(
                    $translator->translate('This module requires the module "%s", version %s or above.'), // @translate
                    $moduleDependency['name'], $moduleDependency['version']
                );
                throw new ModuleCannotInstallException((string) $message);
            }

            if ($module->getState() !== 'active') {
                $translator = $serviceLocator->get('MvcTranslator');
                $message = new Message(
                    $translator->translate('The "%s" module must be active'), // @translate
                    $moduleDependency['name']
                );
                throw new ModuleCannotInstallException((string) $message);
            }
        }
    }

    protected function createFoldersIfTheyDontExist(): void
    {
        foreach ($this->config['folders'] as $folder) {
            $this->checkFolder($folder['path']);
        }
    }

    protected function checkFolder($folderName): void
    {
        $folderPath = OMEKA_PATH . '/' . $folderName;

        if (!is_dir($folderPath)) {
            mkdir($folderPath, 0755, true);
            return;
        }

        // If the directory exists, check if it is writable
        if (!is_writable($folderPath)) {
            $message = sprintf('Directory %s exists, but is not writable. 
            Please make sure it is writable', $folderName);
            throw new ModuleCannotInstallException((string) $message);
        }
    }


    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $sharedEventManager->attach(
            Item::class,
            'entity.update.post',  // Do we need to get the pre or post events?
            [$this, 'dispatchJobs']
        );

        $sharedEventManager->attach(
            Item::class,
            'entity.persist.post', // Do we need to get the pre or post events?
            [$this, 'dispatchJobs']
        );
    }

    public function dispatchJobs(Event $event): void
    {
        $entity = $event->getTarget();

        if (! $entity instanceof Item) {
            return;
        }

        $this->api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $dispatcher = $this->serviceLocator->get('Omeka\Job\Dispatcher');
        $id = $entity->getId();
        $response = $this->api->read('items', $id);
        $content = $response->getContent();
        $resourceTemplate = $content->resourceTemplate();
        $label = $resourceTemplate->label();
        $useBackground = true; // later in config?

        if ( $label === 'LDS Dataset' ) {
            $job = $useBackground
                ? $dispatcher->dispatch(DataDumpJob::class, [ 'id' => $id ]) // async
                : $dispatcher->dispatch(DataDumpJob::class, [ 'id' => $id ], $this->getServiceLocator()->get(Synchronous::class));
        } else {
            if ( $label === 'LDS Datacatalog' ||
                 $label === 'LDS Distribution'
            ) {
                $job = $useBackground
                    ? $dispatcher->dispatch(RecreateDataCatalogsJob::class, []) // async
                    : $dispatcher->dispatch(RecreateDataCatalogsJob::class, [], $this->getServiceLocator()->get(Synchronous::class));
            }
        }
    }
}

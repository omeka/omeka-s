<?php
namespace Omeka\Mvc;

use Omeka\Module as OmekaModule;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class Status implements ServiceLocatorAwareInterface
{
    /**
     * Table against which to check for an Omeka installation
     */
    const CHECK_TABLE = 'user';

    /**
     * @var bool
     */
    protected $isInstalled;

    /**
     * @var bool
     */
    protected $isApiRequest;

    /**
     * Check whether Omeka is currently installed.
     *
     * The heuristic for determining an installed state is the existence of a
     * critical table in the database.
     *
     * If Omeka is found to be installed, we assume it will continue to be
     * installed for the duration of the process. Otherwise, we assume that
     * Omeka continues to be uninstalled and check against the database each
     * time this method is called.
     *
     * @return bool
     */
    public function isInstalled()
    {
        if (true === $this->isInstalled) {
            return true;
        }
        $tables = $this->getServiceLocator()->get('Omeka\Connection')
            ->getSchemaManager()->listTableNames();
        $this->isInstalled = in_array(self::CHECK_TABLE, $tables);
        return $this->isInstalled;
    }

    /**
     * Check whether the current HTTP request is an API request.
     *
     * The heuristic for determining an API request is a route match against the
     * API controller.
     *
     * @return bool
     */
    public function isApiRequest()
    {
        if (null !== $this->isApiRequest) {
            return $this->isApiRequest;
        }
        // Get the route match.
        $router = $this->getServiceLocator()->get('Router');
        $request = $this->getServiceLocator()->get('Request');
        $routeMatch = $router->match($request);
        if (null === $routeMatch) {
            // No matching route; not an API request.
            return false;
        }
        $this->isApiRequest = 'Omeka\Controller\Api'
            === $routeMatch->getParam('controller');
        return $this->isApiRequest;
    }

    /**
     * Check whether Omeka needs a version update.
     *
     * An update is needed when the code version is more recent than the
     * installed version.
     *
     * @return bool
     */
    public function needsVersionUpdate()
    {
        if (version_compare($this->getVersion(), $this->getInstalledVersion(), '=')) {
            return false;
        }
        return true;
    }

    /**
     * Check whether Omeka needs a database migration.
     *
     * A migration is needed when there are un-performed migrations.
     *
     * @return bool
     */
    public function needsMigration()
    {
        $migrationManager = $this->getServiceLocator()->get('Omeka\MigrationManager');
        if ($migrationManager->getMigrationsToPerform()) {
            return true;
        }
        return false;
    }

    /**
     * Get the Omeka code version.
     *
     * @return string
     */
    public function getVersion()
    {
        return OmekaModule::VERSION;
    }

    /**
     * Get the Omeka installed version.
     *
     * @return string
     */
    public function getInstalledVersion()
    {
        return $this->getServiceLocator()->get('Omeka\Settings')->get('version');
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

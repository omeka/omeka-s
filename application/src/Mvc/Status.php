<?php
namespace Omeka\Mvc;

use Composer\Semver\Comparator;
use Omeka\Module as OmekaModule;
use Zend\ServiceManager\ServiceLocatorInterface;

class Status
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

    /**
     * @var bool
     */
    protected $isInstalled;

    /**
     * @var bool
     */
    protected $isApiRequest;

    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * Check whether Omeka is currently installed.
     *
     * @return bool
     */
    public function isInstalled()
    {
        return (bool) $this->isInstalled;
    }

    /**
     * Set whether Omeka is currently installed.
     *
     * Since it's invoked so early in the application's initialization, the
     * module manager is responsible for determining an installed state. The
     * heuristic is the existence of the module table.
     *
     * @param bool $isInstalled
     */
    public function setIsInstalled($isInstalled)
    {
        $this->isInstalled = (bool) $isInstalled;
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
        $router = $this->serviceLocator->get('Router');
        $request = $this->serviceLocator->get('Request');
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
        return Comparator::greaterThan($this->getVersion(), $this->getInstalledVersion());
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
        $migrationManager = $this->serviceLocator->get('Omeka\MigrationManager');
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
        return $this->serviceLocator->get('Omeka\Settings')->get('version');
    }
}

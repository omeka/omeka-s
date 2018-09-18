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

    /**
     * @var bool
     */
    protected $isAdminRequest;

    /**
     * @var bool
     */
    protected $isSiteRequest;

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
     * Get the route match.
     *
     * @return Zend\Router\Http\RouteMatch
     */
    public function getRouteMatch()
    {
        // Attempt to get the route match from the MVC event.
        $routeMatch = $this->serviceLocator->get('Application')->getMvcEvent()->getRouteMatch();
        if (!$routeMatch) {
            // If the match hasn't already been set, calculate it here.
            $router = $this->serviceLocator->get('Router');
            $request = $this->serviceLocator->get('Request');
            $routeMatch = $router->match($request);
        }
        return $routeMatch;
    }

    /**
     * Get a parameter from the matched route.
     *
     * @param string $param
     * @return bool
     */
    public function getRouteParam($param)
    {
        $routeMatch = $this->getRouteMatch();
        return $routeMatch ? $routeMatch->getParam($param) : false;
    }

    /**
     * Check whether the current HTTP request is an API request.
     *
     * @return bool
     */
    public function isApiRequest()
    {
        if (isset($this->isApiRequest)) {
            return $this->isApiRequest;
        }
        $this->isApiRequest = (bool) $this->getRouteParam('__API__');
        return $this->isApiRequest;
    }

    /**
     * Check whether the current HTTP request is an admin request.
     *
     * @return bool
     */
    public function isAdminRequest()
    {
        if (isset($this->isAdminRequest)) {
            return $this->isAdminRequest;
        }
        $this->isAdminRequest = (bool) $this->getRouteParam('__ADMIN__');
        return $this->isAdminRequest;
    }

    /**
     * Check whether the current HTTP request is a site request.
     *
     * @return bool
     */
    public function isSiteRequest()
    {
        if (isset($this->isSiteRequest)) {
            return $this->isSiteRequest;
        }
        $this->isSiteRequest = (bool) $this->getRouteParam('__SITE__');
        return $this->isSiteRequest;
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

<?php
namespace Omeka\Mvc;

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
        $connection = $this->getServiceLocator()->get('Omeka\Connection');
        $config = $this->getServiceLocator()->get('ApplicationConfig');
        $tables = $connection->getSchemaManager()->listTableNames();
        $checkTable = $config['connection']['table_prefix'] . self::CHECK_TABLE;
        $this->isInstalled = in_array($checkTable, $tables);
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
        $this->isApiRequest = 'Omeka\Controller\Api'
            === $routeMatch->getParam('controller');
        return $this->isApiRequest;
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

<?php
namespace EADImport\Mapping;

use Omeka\Api\Manager as ApiManager;
use Laminas\Log\Logger;
use Laminas\ServiceManager\ServiceLocatorInterface;

abstract class AbstractMapping implements MappingInterface
{
    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $args;

    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ApiManager
     */
    protected $api;

    /**
     * @var bool
     */
    protected $hasErr = false;

    public function getLabel()
    {
        return $this->label;
    }

    public function getName()
    {
        return $this->name;
    }

    public function init(array $args, ServiceLocatorInterface $serviceLocator)
    {
        $this->args = $args;
        $this->serviceLocator = $serviceLocator;
        $this->logger = $serviceLocator->get('Omeka\Logger');
        $this->api = $serviceLocator->get('Omeka\ApiManager');
    }

    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    public function setHasErr($hasErr)
    {
        $this->hasErr = $hasErr;
    }

    public function getHasErr()
    {
        return $this->hasErr;
    }
}

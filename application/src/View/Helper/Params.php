<?php
namespace Omeka\View\Helper;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Helper\AbstractHelper;

/**
 * Helper to get params from the request.
 */
class Params extends AbstractHelper
{
    /**
     * @var \Zend\Mvc\Controller\Plugin\Params
     */
    protected $params;

    /**
     * Construct the helper.
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->params = $serviceLocator->get('ControllerPluginManager')
            ->get('Params');
    }

    public function fromFiles($name = null, $default = null)
    {
        return $this->params->fromFiles($name, $default);
    }

    public function fromHeader($header = null, $default = null)
    {
        return $this->params->fromHeader($header, $default);
    }

    public function fromPost($param = null, $default = null)
    {
        return $this->params->fromPost($param, $default);
    }

    public function fromQuery($param = null, $default = null)
    {
        return $this->params->fromQuery($param, $default);
    }

    public function fromRoute($param = null, $default = null)
    {
        return $this->params->fromRoute($param, $default);
    }
}

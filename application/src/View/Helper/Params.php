<?php
namespace Omeka\View\Helper;

use Zend\Mvc\Controller\Plugin\Params as ParamsPlugin;
use Zend\View\Helper\AbstractHelper;

/**
 * View helper for getting params from the request.
 */
class Params extends AbstractHelper
{
    /**
     * @var ParamsPlugin
     */
    protected $params;

    /**
     * Construct the helper.
     *
     * @param ParamsPlugin $params
     */
    public function __construct(ParamsPlugin $params)
    {
        $this->params = $params;
    }

    public function fromFiles($name = null, $default = null)
    {
        if (!$this->params->getController()) {
            return $default;
        }
        return $this->params->fromFiles($name, $default);
    }

    public function fromHeader($header = null, $default = null)
    {
        if (!$this->params->getController()) {
            return $default;
        }
        return $this->params->fromHeader($header, $default);
    }

    public function fromPost($param = null, $default = null)
    {
        if (!$this->params->getController()) {
            return $default;
        }
        return $this->params->fromPost($param, $default);
    }

    public function fromQuery($param = null, $default = null)
    {
        if (!$this->params->getController()) {
            return $default;
        }
        return $this->params->fromQuery($param, $default);
    }

    public function fromRoute($param = null, $default = null)
    {
        if (!$this->params->getController()) {
            return $default;
        }
        return $this->params->fromRoute($param, $default);
    }
}

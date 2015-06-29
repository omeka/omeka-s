<?php
namespace Omeka\Service;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class HtmlPurifier implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    protected $config;

    public function purify($html)
    {
        $config = $this->getConfig();
        $purifier = new \HTMLPurifier($config);
        $html = $purifier->purify($html);
        return $html;
    }

    public function getConfig()
    {
        if (is_null($this->config)) {
            $this->config = \HTMLPurifier_Config::createDefault();
        }
        return $this->config;
    }
}
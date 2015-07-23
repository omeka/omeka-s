<?php
namespace Omeka\Service;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class HtmlPurifier implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    protected $config;
    protected $purifier;
    protected $useHtmlPurifier;

    public function __construct($useHtmlPurifier)
    {
        $this->useHtmlPurifier = $useHtmlPurifier;
    }

    public function purify($html)
    {
        if ($this->useHtmlPurifier) {
            $purifier = $this->getPurifier();
            $html = $purifier->purify($html);
        }
        return $html;
    }

    public function getConfig()
    {
        if (is_null($this->config)) {
            $this->config = \HTMLPurifier_Config::createDefault();
        }
        return $this->config;
    }

    protected function getPurifier()
    {
        if (is_null($this->purifier)) {
            $config = $this->getConfig();
            $config->set('Cache.DefinitionImpl', null);
            $this->purifier = new \HTMLPurifier($config);
        }
        return $this->purifier;
    }
}

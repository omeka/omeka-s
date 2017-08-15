<?php
namespace Omeka\Stdlib;

class HtmlPurifier
{
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
        if ($this->config === null) {
            $this->config = \HTMLPurifier_Config::createDefault();
        }
        return $this->config;
    }

    protected function getPurifier()
    {
        if ($this->purifier === null) {
            $config = $this->getConfig();
            $config->set('Cache.DefinitionImpl', null);
            $this->purifier = new \HTMLPurifier($config);
        }
        return $this->purifier;
    }
}

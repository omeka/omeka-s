<?php
namespace Omeka\Stdlib;

use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerAwareTrait;

class HtmlPurifier implements EventManagerAwareInterface
{
    use EventManagerAwareTrait;

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
            $config = \HTMLPurifier_Config::createDefault();
            $config->set('Attr.AllowedFrameTargets', ['_blank']);
            $config->set('Cache.DefinitionImpl', null);

            $events = $this->getEventManager();
            $args = $events->prepareArgs([
                'config' => $config,
            ]);
            $events->trigger('htmlpurifier_config', $this, $args);
            $this->config = $args['config'];
        }

        return $this->config;
    }

    protected function getPurifier()
    {
        if ($this->purifier === null) {
            $this->purifier = new \HTMLPurifier($this->getConfig());
        }
        return $this->purifier;
    }
}

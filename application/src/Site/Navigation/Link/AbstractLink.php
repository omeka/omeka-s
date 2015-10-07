<?php
namespace Omeka\Site\Navigation\Link;

use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\View\HelperPluginManager;

abstract class AbstractLink implements LinkInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * @var HelperPluginManager
     */
    protected $viewHelperManager;

    /**
     * Get a view helper from the manager.
     *
     * @param string $name
     * @return TranslatorInterface
     */
    protected function getViewHelper($name)
    {
        if (!$this->viewHelperManager instanceof HelperPluginManager) {
            $this->viewHelperManager = $this->getServiceLocator()
                ->get('ViewHelperManager');
        }
        return $this->viewHelperManager->get($name);
    }
}

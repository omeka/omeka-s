<?php
namespace Omeka\Mvc\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\View\HelperPluginManager;

/**
 * Controller plugin for getting the view helper plugin manager.
 */
class ViewHelpers extends AbstractPlugin
{
    /**
     * @var HelperPluginManager
     */
    protected $viewHelpers;

    /**
     * Construct the plugin.
     *
     * @param HelperPluginManager $viewHelpers
     */
    public function __construct(HelperPluginManager $viewHelpers)
    {
        $this->viewHelpers = $viewHelpers;
    }

    /**
     * Get the view helper plugin manager.
     *
     * @return HelperPluginManager
     */
    public function __invoke()
    {
        return $this->viewHelpers;
    }
}

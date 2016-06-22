<?php
namespace Omeka\Mvc\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\View\HelperPluginManager;

class ViewHelpers extends AbstractPlugin
{
    /**
     * @var HelperPluginManager
     */
    protected $viewHelpers;

    public function __construct(HelperPluginManager $viewHelpers)
    {
        $this->viewHelpers = $viewHelpers;
    }

    public function __invoke()
    {
        return $this->viewHelpers;
    }
}

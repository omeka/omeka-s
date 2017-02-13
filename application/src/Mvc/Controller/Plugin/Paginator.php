<?php
namespace Omeka\Mvc\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\View\HelperPluginManager;

class Paginator extends AbstractPlugin
{
    /**
     * @var HelperPluginManager
     */
    protected $viewHelpers;

    public function __construct(HelperPluginManager $viewHelpers)
    {
        $this->viewHelpers = $viewHelpers;
    }

    public function __invoke($totalCount, $currentPage, $perPage = null, $name = null)
    {
        $pagination = $this->viewHelpers->get('pagination');
        $pagination($name, $totalCount, $currentPage, $perPage);
    }
}

<?php
namespace Omeka\Mvc\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\View\HelperPluginManager;

/**
 * Controller plugin for setting variables to the pagination view helper.
 */
class Paginator extends AbstractPlugin
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
     * Set variables to the pagination view helper.
     *
     * @param int|null $totalCount The total record count
     * @param int|null $currentPage The current page number
     * @param int|null $perPage The number of records per page
     * @param string|null $partialName Name of view script
     */
    public function __invoke($totalCount, $currentPage, $perPage = null, $partialName = null)
    {
        $pagination = $this->viewHelpers->get('pagination');
        $pagination($partialName, $totalCount, $currentPage, $perPage);
    }
}

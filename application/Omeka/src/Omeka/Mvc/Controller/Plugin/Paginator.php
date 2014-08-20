<?php
namespace Omeka\Mvc\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class Paginator extends AbstractPlugin
{
    public function __invoke($totalCount, $currentPage, $perPage = null,
        $name = null
    ) {
        $pagination = $this->getController()
            ->getServiceLocator()
            ->get('ViewHelperManager')
            ->get('pagination');
        $pagination($totalCount, $currentPage, $perPage, $name);
    }
}

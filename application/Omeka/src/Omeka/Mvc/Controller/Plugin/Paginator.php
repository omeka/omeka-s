<?php
namespace Omeka\Mvc\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class Paginator extends AbstractPlugin
{
    public function __invoke($totalCount, $page, $perPage = null)
    {
        $pagination = $this->getController()
            ->getServiceLocator()
            ->get('ViewHelperManager')
            ->get('pagination');
        $pagination($totalCount, $page, $perPage);
    }
}

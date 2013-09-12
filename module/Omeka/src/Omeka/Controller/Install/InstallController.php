<?php
namespace Omeka\Controller\Install;

use Omeka\Install;
use Omeka\Controller\AbstractRestfulController;
use Zend\View\Model\ViewModel;

class InstallController extends AbstractRestfulController
{
    public function indexAction()
    {
        $this->install();
        return new ViewModel();
    }
    
    protected function install()
    {
        $installer = new \Omeka\Install\Install;
        $installer->addTask('schema');
    }
}
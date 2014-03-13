<?php
namespace Omeka\Mvc\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class Api extends AbstractPlugin
{
    /**
     * Return the API manager.
     *
     * @return 
     */
    public function __invoke()
    {
        return $this->getController()->getServiceLocator()->get('Omeka\ApiManager');
    }
}

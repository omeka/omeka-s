<?php
namespace Omeka\Mvc\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class GetForm extends AbstractPlugin
{
    public function __invoke($class) {
        return $this->getController()->getServiceLocator()
            ->get('FormElementManager')->get($class);
    }
}

<?php
namespace Omeka\Service\Form;

use Omeka\Form\ResourceForm;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ResourceFormFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $elements)
    {
        $form = new ResourceForm;
        $form->setUrlHelper($elements->getServiceLocator()->get('ViewHelperManager')->get('Url'));
        return $form;
    }
}

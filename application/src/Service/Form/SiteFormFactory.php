<?php
namespace Omeka\Service\Form;

use Omeka\Form\SiteForm;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class SiteFormFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $elements)
    {
        $form = new SiteForm;
        $form->setThemeManager($elements->getServiceLocator()->get('Omeka\Site\ThemeManager'));
        return $form;
    }
}

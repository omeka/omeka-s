<?php
namespace Omeka\Service\Form;

use Omeka\Form\SiteSettingsForm;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class SiteSettingsFormFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $elements)
    {
        $form = new SiteSettingsForm;
        $form->setSiteSettings($elements->getServiceLocator()->get('Omeka\SiteSettings'));
        return $form;
    }
}

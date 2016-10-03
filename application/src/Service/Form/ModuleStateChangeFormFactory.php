<?php
namespace Omeka\Service\Form;

use Omeka\Form\ModuleStateChangeForm;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ModuleStateChangeFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new ModuleStateChangeForm(null, $options);
        $form->setUrlHelper($services->get('ViewHelperManager')->get('Url'));
        return $form;
    }
}

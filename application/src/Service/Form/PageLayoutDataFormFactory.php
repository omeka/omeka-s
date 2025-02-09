<?php
namespace Omeka\Service\Form;

use Omeka\Form\PageLayoutDataForm;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class PageLayoutDataFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new PageLayoutDataForm;
        $form->setCurrentTheme($services->get('Omeka\Site\ThemeManager')->getCurrentTheme());
        return $form;
    }
}

<?php
namespace Omeka\Service\Form;

use Omeka\Form\BlockLayoutDataForm;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class BlockLayoutDataFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new BlockLayoutDataForm;
        $form->setCurrentTheme($services->get('Omeka\Site\ThemeManager')->getCurrentTheme());
        $form->setViewHelpers($services->get('ViewHelperManager'));
        return $form;
    }
}

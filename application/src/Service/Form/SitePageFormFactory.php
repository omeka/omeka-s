<?php
namespace Omeka\Service\Form;

use Omeka\Form\SitePageForm;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class SitePageFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new SitePageForm(null, $options ?? []);
        $form->setCurrentTheme($services->get('Omeka\Site\ThemeManager')->getCurrentTheme());
        return $form;
    }
}

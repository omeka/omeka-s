<?php
namespace Omeka\Service\Form;

use Omeka\Form\ResourceBatchUpdateForm;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ResourceBatchUpdateFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new ResourceBatchUpdateForm(null, $options);
        $form->setUrlHelper($services->get('ViewHelperManager')->get('Url'));
        $form->setAcl($services->get('Omeka\Acl'));
        $form->setEventManager($services->get('EventManager'));
        return $form;
    }
}

<?php declare(strict_types=1);

namespace Omeka\Service\Form;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class FormWithEventManagerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new $requestedName(null, $options ?? []);
        $form->setEventManager($services->get('EventManager'));
        return $form;
    }
}

<?php
namespace Omeka\Service\Delegator;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\DelegatorFactoryInterface;

class FormSelectDelegatorFactory implements DelegatorFactoryInterface
{
    public function __invoke(ContainerInterface $container, $name,
        callable $callback, array $options = null
    ) {
        $formSelect = $callback();
        // The data-placeholder attribute is used by Chosen to display default
        // field text. This will make sure that attribute is translated.
        $formSelect->addTranslatableAttribute('data-placeholder');
        return $formSelect;
    }
}

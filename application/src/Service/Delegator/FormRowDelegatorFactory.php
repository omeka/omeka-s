<?php
namespace Omeka\Service\Delegator;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\DelegatorFactoryInterface;

/**
 * Set the custom form row partial.
 */
class FormRowDelegatorFactory implements DelegatorFactoryInterface
{
    public function __invoke(ContainerInterface $container, $name,
        callable $callback, array $options = null
    ) {
        $formRow = $callback();
        $formRow->setPartial('common/form-row');
        return $formRow;
    }
}

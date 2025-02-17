<?php
namespace NumericDataTypes\Service\Delegator;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\DelegatorFactoryInterface;

class FormElementDelegatorFactory implements DelegatorFactoryInterface
{
    public function __invoke(ContainerInterface $container, $name,
        callable $callback, array $options = null
    ) {
        $formElement = $callback();
        $formElement->addClass(
            \NumericDataTypes\Form\Element\Timestamp::class,
            'formNumericTimestamp'
        );
        $formElement->addClass(
            \NumericDataTypes\Form\Element\Interval::class,
            'formNumericInterval'
        );
        $formElement->addClass(
            \NumericDataTypes\Form\Element\Duration::class,
            'formNumericDuration'
        );
        $formElement->addClass(
            \NumericDataTypes\Form\Element\Integer::class,
            'formNumericInteger'
        );
        $formElement->addClass(
            \NumericDataTypes\Form\Element\ConvertToNumeric::class,
            'formNumericConvertToNumeric'
        );
        return $formElement;
    }
}

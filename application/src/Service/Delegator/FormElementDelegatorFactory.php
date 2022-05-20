<?php
namespace Omeka\Service\Delegator;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\DelegatorFactoryInterface;

/**
 * Map custom element types to the view helpers that render them.
 */
class FormElementDelegatorFactory implements DelegatorFactoryInterface
{
    public function __invoke(ContainerInterface $container, $name,
        callable $callback, array $options = null
    ) {
        $formElement = $callback();
        $formElement->addType('recaptcha', 'formRecaptcha');
        $formElement->addType('ckeditor', 'formCkeditor');
        $formElement->addType('ckeditor_inline', 'formCkeditorInline');
        $formElement->addType('restore_textarea', 'formRestoreTextarea');
        $formElement->addType('color_picker', 'formColorPicker');
        $formElement->addClass('Omeka\Form\Element\Asset', 'formAsset');
        $formElement->addClass('Omeka\Form\Element\Query', 'formQuery');
        $formElement->addClass('Omeka\Form\Element\Columns', 'formColumns');
        $formElement->addClass('Omeka\Form\Element\BrowseDefaults', 'formBrowseDefaults');
        return $formElement;
    }
}

<?php
namespace Omeka\View\Helper;

use Zend\Form\ElementInterface;
use Zend\View\Helper\AbstractHelper;

class FormField extends AbstractHelper
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/form-element';

    /**
     * Render a "field" for a form element.
     *
     * For normal elements, the field includes the label, actual element, any
     * error output, and all the markup tying those together. Hidden elements
     * and those without labels just have the bare element printed.
     *
     * @param ElementInterface $element
     * @param string $partialName Name of view script, or a view model
     */
    public function __invoke(ElementInterface $element, $partialName = null)
    {
        $partialName = $partialName ?: self::PARTIAL_NAME;

        $label = $element->getLabel();
        $type = $element->getAttribute('type');
        if (null === $label || 'hidden' === $type) {
            return $this->getView()->formElement($element);
        }

        return $this->getView()->partial($partialName, array(
            'element' => $element,
        ));
    }
}

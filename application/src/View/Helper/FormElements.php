<?php
namespace Omeka\View\Helper;

use Zend\Form\Element\Csrf;
use Zend\Form\Form;
use Zend\View\Helper\AbstractHelper;

class FormElements extends AbstractHelper
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/form-element';

    /**
     * Render the form.
     *
     * @param Form $form
     * @param string $partialName Name of view script, or a view model
     */
    public function __invoke(Form $form, $partialName = null)
    {
        $partialName = $partialName ?: self::PARTIAL_NAME;

        $markup = '';
        foreach ($form->getElements() as $element) {
            $label = $element->getLabel();
            $type = $element->getAttribute('type');
            if (null === $label || 'hidden' === $type) {
                $markup .= $this->getView()->formElement($element);
            } else {
                $markup .= $this->getView()->partial($partialName, array(
                    'element' => $element,
                ));
            }
        }
        return $markup;
    }
}

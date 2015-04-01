<?php
namespace Omeka\View\Helper;

use Zend\Form\Form;
use Zend\View\Helper\AbstractHelper;

class FormElements extends AbstractHelper
{
    /**
     * Render the form.
     *
     * @param Form $form
     * @param string $partialName Name of view script, or a view model
     */
    public function __invoke(Form $form, $partialName = null)
    {
        $formField = $this->view->plugin('formField');

        $markup = '';
        foreach ($form->getElements() as $element) {
            $markup .= $formField($element, $partialName);
        }
        return $markup;
    }
}

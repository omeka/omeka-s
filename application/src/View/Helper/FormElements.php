<?php
namespace Omeka\View\Helper;

use Zend\Form\Element\Csrf;
use Zend\Form\Form;
use Zend\View\Helper\AbstractHelper;

class FormElements extends AbstractHelper
{
    /**
     * Name of view script, or a view model
     *
     * @var string|\Zend\View\Model\ModelInterface
     */
    protected $name = 'common/form-element';

    /**
     * Render the form.
     *
     * @param Form $form
     * @param string $name Name of view script, or a view model
     */
    public function __invoke(Form $form, $name = null)
    {
        $name = $name ?: $this->name;

        $markup = '';
        foreach ($form->getElements() as $element) {
            $label = $element->getLabel();
            $type = $element->getAttribute('type');
            if (null === $label || 'hidden' === $type) {
                $markup .= $this->getView()->formElement($element);
            } else {
                $markup .= $this->getView()->partial($name, array(
                    'element' => $element,
                ));
            }
        }
        return $markup;
    }
}

<?php
namespace Omeka\Form\View\Helper;

use Laminas\Form\View\Helper\FormTextarea;
use Laminas\Form\ElementInterface;

/**
 * Render a textarea with a CKEditor HTML editor enabled.
 */
class FormCkeditor extends FormTextarea
{
    public function render(ElementInterface $element)
    {
        $id = $element->getAttribute('id');
        $textarea = parent::render($element);

        if (!$id) {
            // The CKEditor must have the element's id.
            return $textarea;
        }

        return sprintf(
            '%s<script type="text/javascript">$("#%s").ckeditor();</script>',
            $textarea,
            $this->getView()->escapeJs($id)
        );
    }
}

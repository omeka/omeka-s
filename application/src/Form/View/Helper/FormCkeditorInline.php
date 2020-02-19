<?php
namespace Omeka\Form\View\Helper;

use Laminas\Form\View\Helper\FormTextarea;
use Laminas\Form\ElementInterface;

/**
 * Render a textarea with an inline CKEditor HTML editor enabled.
 */
class FormCkeditorInline extends FormTextarea
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
            '%s<script type="text/javascript">CKEDITOR.inline("%s");</script>',
            $textarea,
            $this->getView()->escapeJs($id)
        );
    }
}

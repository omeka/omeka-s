<?php
namespace Omeka\Form\View\Helper;

use Zend\Form\View\Helper\FormText;
use Zend\Form\ElementInterface;

class FormColorPicker extends FormText
{
    public function render(ElementInterface $element)
    {
        $view = $this->getView();
        $view->headScript()->appendFile($view->assetUrl('js/color-picker.js', 'Omeka'));
        return '
<div class="color-picker">'
    . parent::render($element) .
    '<div class="color-picker-sample"></div>
</div>';
    }
}

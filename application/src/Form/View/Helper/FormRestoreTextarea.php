<?php
namespace Omeka\Form\View\Helper;

use Zend\Form\View\Helper\FormTextarea;
use Zend\Form\ElementInterface;

class FormRestoreTextarea extends FormTextarea
{
    public function render(ElementInterface $element)
    {
        $view = $this->getView();
        $view->headScript()->appendFile($view->assetUrl('js/restore-textarea.js', 'Omeka'));
        return sprintf(
            '<div>%s<button type="button" class="restore-textarea" data-restore-value="%s">%s</button></div>',
            $textarea = parent::render($element),
            $view->escapeHtml($element->getRestoreValue()),
            $view->escapeHtml($view->translate($element->getRestoreButtonText()))
        );
    }
}

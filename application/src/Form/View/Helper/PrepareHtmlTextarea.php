<?php
namespace Omeka\Form\View\Helper;

use Zend\Form\View\Helper\AbstractHelper;

/**
 * Prepare all HtmlTextarea elements on the page.
 *
 * You must call this helper before rendering the elements.
 */
class PrepareHtmlTextarea extends AbstractHelper
{
    public function __invoke()
    {
        $view = $this->getView();

        // Initialize the CKEditor.
        $view->ckEditor();

        // Map the HtmlTextarea element type to the view helper that renders it.
        $view->formElement()->addType('htmltextarea', 'formHtmlTextarea');

    }
}

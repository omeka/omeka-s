<?php
namespace Omeka\Form\View\Helper;

use Zend\Form\View\Helper\AbstractHelper;

/**
 * Prepare all reCAPTCHA elements on the page. You must call this helper before
 * rendering the elements.
 *
 * @see https://developers.google.com/recaptcha/docs/display#explicit_render
 */
class PrepareRecaptcha extends AbstractHelper
{
    public function __invoke()
    {
        $view = $this->getView();

        // Map the reCAPTCHA element type to the view helper that renders it.
        $view->formElement()->addType('recaptcha', 'formRecaptcha');

        // Render the reCAPTCHA elements. The callback must be defined before
        // the reCAPTCHA API loads.
        $view->headScript()->appendScript('
var recaptchaCallback = function() {
    $(".g-recaptcha").each(function() {
        grecaptcha.render(this, {"sitekey" : $(this).data("sitekey")});
    });
};');
        $view->headScript()->appendFile(
            'https://www.google.com/recaptcha/api.js?onload=recaptchaCallback&render=explicit',
            'text/javascript',
            ['async' => true, 'defer' => true]
        );
    }
}

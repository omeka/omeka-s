/**
 * Callback used to render all reCAPTCHA elements on the page.
 *
 * @see Omeka\Form\View\Helper\FormRecaptcha
 */
var recaptchaCallback = function() {
    $(".g-recaptcha").each(function() {
        grecaptcha.render(this, {"sitekey" : $(this).data("sitekey")});
    });
};

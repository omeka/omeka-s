/**
 * Callback used to render all reCAPTCHA elements on the page.
 *
 * Google's API calls this by name once it loads (see its "onload" parameter),
 * so it must stay global. Elements that already have a widget are skipped:
 * when the page also loads the API without explicit rendering, as reCAPTCHA
 * v3 does, Google renders them itself first.
 *
 * @see Omeka\Form\View\Helper\FormRecaptcha
 * @see Omeka\Captcha\RecaptchaV2
 */
window.recaptchaCallback = function() {
    document.querySelectorAll('.g-recaptcha').forEach(function(element) {
        if (element.childElementCount) {
            return;
        }
        grecaptcha.render(element, {sitekey: element.dataset.sitekey});
    });
};

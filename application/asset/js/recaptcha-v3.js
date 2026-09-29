/**
 * Get a reCAPTCHA v3 token when a form containing a reCAPTCHA v3 input is
 * submitted, then submit the form with it.
 *
 * Tokens expire after two minutes, so get one at submit time rather than on
 * page load. The API script is loaded before this one and without async, so
 * it's available before anyone can submit.
 *
 * @see Omeka\Captcha\RecaptchaV3
 */
document.addEventListener('submit', function(e) {
    const form = e.target;
    const input = form.querySelector('input.recaptcha-v3');
    // Not a v3 form, another handler cancelled the submit, or the API didn't
    // load (the server then rejects the form with an error).
    if (!input || e.defaultPrevented || 'undefined' === typeof grecaptcha) {
        return;
    }
    e.preventDefault();
    // Already getting a token, e.g. after a double click.
    if (input.dataset.pending) {
        return;
    }
    input.dataset.pending = '1';
    // form.submit() may be shadowed by a field named "submit", as in Collecting.
    const submit = function() {
        HTMLFormElement.prototype.submit.call(form);
    };
    grecaptcha.ready(function() {
        try {
            grecaptcha.execute(input.dataset.sitekey, {action: input.dataset.action})
                .then(function(token) {
                    input.value = token;
                    submit();
                }, submit);
        } catch (error) {
            // An invalid site key throws. Submit so the server shows an error.
            submit();
        }
    });
});

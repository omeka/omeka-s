/**
 * Get a reCAPTCHA v3 token when a form containing a reCAPTCHA v3 input is
 * submitted, then submit the form with it.
 *
 * Tokens expire after two minutes, so get one at submit time rather than on
 * page load.
 *
 * @see Omeka\Captcha\RecaptchaV3
 */
(function() {

// Milliseconds to wait for the API and a token before submitting without one.
const TOKEN_TIMEOUT = 10000;

// Milliseconds between checks for the API, which loads asynchronously.
const API_POLL_INTERVAL = 100;

// Forms waiting for a token, so a repeated submit doesn't request another.
const pending = new WeakSet();

// The form being resubmitted with a token, which the handler lets through.
let resubmitting = null;

document.addEventListener('submit', function(e) {
    const form = e.target;
    const isResubmit = (resubmitting === form);
    resubmitting = null;

    const input = form.querySelector('input.recaptcha-v3');
    // Let another handler's cancellation stand, and don't get a token twice.
    if (!input || e.defaultPrevented || isResubmit) {
        return;
    }
    e.preventDefault();
    // Already getting a token for this form, e.g. after a double click.
    if (pending.has(form)) {
        return;
    }
    pending.add(form);

    const submitter = e.submitter;
    let submitted = false;
    const submitWithToken = function(token) {
        if (submitted) {
            return;
        }
        submitted = true;
        clearTimeout(timeout);
        pending.delete(form);
        input.value = token;
        resubmitting = form;
        // Unlike submit(), requestSubmit() keeps the submit button's value.
        form.requestSubmit(submitter);
    };
    const submitWithoutToken = function() {
        submitWithToken('');
    };
    // Whatever goes wrong getting a token, including the API never loading,
    // submit anyway so the server responds with an error rather than the form
    // doing nothing.
    const timeout = setTimeout(submitWithoutToken, TOKEN_TIMEOUT);

    const getToken = function() {
        try {
            // Throws, rather than rejecting, when the site key is invalid.
            window.grecaptcha.execute(input.dataset.sitekey, {action: input.dataset.action})
                .then(submitWithToken, submitWithoutToken);
        } catch (error) {
            submitWithoutToken();
        }
    };
    const waitForApi = function() {
        if (submitted) {
            return;
        }
        if (window.grecaptcha && window.grecaptcha.ready) {
            window.grecaptcha.ready(getToken);
        } else {
            setTimeout(waitForApi, API_POLL_INTERVAL);
        }
    };
    waitForApi();
});

})();

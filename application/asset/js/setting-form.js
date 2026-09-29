$(document).ready(function() {

// Show only the settings of the selected CAPTCHA provider. Hide the others
// rather than disabling them, so their values are still saved. Always show a
// field that has an error, so it can be found and fixed.
const captchaSelect = $('#captcha');
const toggleCaptchaSettings = function() {
    $('[data-captcha]').each(function() {
        const thisInput = $(this);
        const field = thisInput.closest('.field');
        const isSelected = thisInput.attr('data-captcha') === captchaSelect.val();
        const hasError = field.find('.messages').length > 0;
        field.toggle(isSelected || hasError);
    });
};
toggleCaptchaSettings();
captchaSelect.on('change', toggleCaptchaSettings);

});

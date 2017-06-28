$(document).ready(function () {

$(document).on(
    'o:data-type-options-form-render',
    function(e, dataType, options, optionsForm) {
        if ('resource' !== dataType) {
            return;
        }
        optionsForm = $(optionsForm);
        if (options) {
            if (options.item) {
                optionsForm.find('input[name="item"]').prop('checked', true);
            }
            if (options.itemSet) {
                optionsForm.find('input[name="itemSet"]').prop('checked', true);
            }
            if (options.media) {
                optionsForm.find('input[name="media"]').prop('checked', true);
            }
        } else {
            optionsForm.find('input[name="item"], input[name="itemSet"]').prop('checked', true);
        }
    }
);

$(document).on(
    'o:data-type-options-form-set-changes',
    function(e, dataType, optionsForm, optionsInput) {
        if ('resource' !== dataType) {
            return;
        }
        optionsForm = $(optionsForm);
        var options = {
            item: optionsForm.find('input[name="item"]').prop('checked'),
            itemSet: optionsForm.find('input[name="itemSet"]').prop('checked'),
            media: optionsForm.find('input[name="media"]').prop('checked'),
        };
        optionsInput.value = JSON.stringify(options);
    }
);

});

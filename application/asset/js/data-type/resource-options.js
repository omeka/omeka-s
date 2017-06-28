$(document).ready(function () {

$(document).on(
    'o:data-type-options-form-render',
    function(e, dataType, options, optionsForm) {
        if ('resource' !== dataType) {
            return;
        }
    }
);

$(document).on(
    'o:data-type-options-form-set-changes',
    function(e, dataType, optionsForm, optionsInput) {
        if ('resource' !== dataType) {
            return;
        }
    }
);

});

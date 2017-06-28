$(document).ready(function () {

$(document).on(
    'o:data-type-options-form-render',
    function(e, dataType, options, optionsForm) {
        if ('resource' !== dataType) {
            return;
        }
        console.log(dataType);
        console.log(options);
        console.log(optionsForm);
    }
);

$(document).on(
    'o:data-type-options-form-set-changes',
    function(e, dataType, optionsForm, optionsInput) {
        if ('resource' !== dataType) {
            return;
        }
        console.log(dataType);
        console.log(optionsForm);
        console.log(optionsInput);
    }
);

});

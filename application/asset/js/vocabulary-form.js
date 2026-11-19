$(document).ready(function() {

const fileUpload = $('#file-upload');
const fileUrl = $('#file-url');
const fileUploadField = fileUpload.closest('.field');
const fileUrlField = fileUrl.closest('.field');

const selectedImportType = $('.import-type-select:checked');
if ('upload' === selectedImportType.val()) {
    fileUploadField.show();
    fileUrlField.hide();
} else if ('url' === selectedImportType.val()) {
    fileUploadField.hide();
    fileUrlField.show();
}

$('.import-type-select').on('change', function(e) {
    const thisRadio = $(this);
    if ('upload' === thisRadio.val()) {
        fileUploadField.show();
        fileUrlField.hide();
    } else if ('url' === thisRadio.val()) {
        fileUploadField.hide();
        fileUrlField.show();
    }
});

// The Comments from and Preferred language fields: the text field each has
// for another property or language is shown only when that choice is made.
$('[data-other-field]').each(function() {
    const choice = $(this);
    const field = $(choice.data('otherField')).closest('.field');
    const toggle = function() {
        field.toggle('other' === choice.val());
    };
    toggle();
    choice.on('change', toggle);
});

$('#vocabulary-form').on('submit', function(e) {
    // Only the import details and Edit forms have a namespace URI.
    const namespaceUriInput = $('#o\\:namespace_uri');
    if (!namespaceUriInput.length) {
        return;
    }
    // Show classes and properties, and Review changes, import nothing, so
    // have nothing to confirm.
    const submitter = e.originalEvent && e.originalEvent.submitter;
    if (submitter && ['preview', 'update'].includes(submitter.name)) {
        return;
    }
    // The user must confirm that a namespace URI does not end with / or #,
    // unless it's the vocabulary's own, unchanged.
    const namespaceUri = namespaceUriInput.val();
    if (namespaceUri === namespaceUriInput.attr('data-saved-namespace-uri')) {
        return;
    }
    if (!(namespaceUri.endsWith('/') || namespaceUri.endsWith('#'))) {
        if (!window.confirm(namespaceUriInput.data('confirmEndsWith'))) {
            e.preventDefault();
        }
    }
});

});

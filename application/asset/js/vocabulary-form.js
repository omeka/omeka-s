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

$('#vocabulary-form').on('submit', function(e) {
    const namespaceUriInput = $('#o\\:namespace_uri');
    const namespaceUri = namespaceUriInput.val();
    const originalNamespaceUri = namespaceUriInput.data('originalNamespaceUri');

    // The user must confirm that the namespace URI has changed.
    if (originalNamespaceUri && (namespaceUri !== originalNamespaceUri)) {
        if (!window.confirm(namespaceUriInput.data('confirmChange'))) {
            e.preventDefault();
        }
    }

    // The user must confirm that the namespace URI does not end with / or #.
    if (!(namespaceUri.endsWith('/') || namespaceUri.endsWith('#'))) {
        if (!window.confirm(namespaceUriInput.data('confirmEndsWith'))) {
            e.preventDefault();
        }
    }
});

});

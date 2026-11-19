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

// Whether a field's value differs from the one the page was drawn with.
const isChanged = function(field) {
    if ('SELECT' === field.tagName) {
        // Drawn with no option selected, a select shows its first.
        const drawn = [...field.options].findIndex((option) => option.defaultSelected);
        return field.selectedIndex !== Math.max(0, drawn);
    }
    if ('checkbox' === field.type || 'radio' === field.type) {
        return field.checked !== field.defaultChecked;
    }
    return field.value !== field.defaultValue;
};

// Under a Show button: says the result below was shown with other values
// while a field it depends on differs from the one the page was drawn with,
// as after a change, or when the browser restores changed values on Back.
$('.review-stale').each(function() {
    const note = $(this);
    const fields = note.closest('form').find(note.data('watch'));
    const update = function() {
        note.prop('hidden', !fields.toArray().some(isChanged));
    };
    fields.on('input change', update);
    $(window).on('pageshow', update);
    update();
});

$('#vocabulary-form').on('submit', function(e) {
    // Only the import details and Edit forms have a namespace URI.
    const namespaceUriInput = $('#o\\:namespace_uri');
    if (!namespaceUriInput.length) {
        return;
    }
    // A button that skips the browser's checks, as Show classes and
    // properties and Review changes do, imports nothing, so has nothing to
    // confirm.
    const submitter = e.originalEvent && e.originalEvent.submitter;
    if (submitter && submitter.formNoValidate) {
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

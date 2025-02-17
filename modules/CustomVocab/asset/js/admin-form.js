$(document).ready(function() {

const termTypeLiteral = $('.vocab-type[value="literal"]');
const termTypeResource = $('.vocab-type[value="resource"]');
const termTypeUri = $('.vocab-type[value="uri"]');

const itemSetSelect = $('#o-item-set');
const itemSetField = itemSetSelect.closest('.field');

const termsTextarea = $('#o-terms');
const termsField = termsTextarea.closest('.field');

const urisTextarea = $('#o-uris');
const urisField = urisTextarea.closest('.field');

// Prepare the form when document is ready.
if (itemSetSelect.val()) {
    termTypeResource.prop('checked', true);
    termsField.hide();
    urisField.hide();
} else if ('' !== urisTextarea.val()) {
    termTypeUri.prop('checked', true);
    itemSetField.hide();
    termsField.hide();
} else {
    termTypeLiteral.prop('checked', true);
    itemSetField.hide();
    urisField.hide();
}

// Handle vocab type change.
$('.vocab-type').on('change', function(e) {
    const thisRadio = $(this);
    if ('resource' === thisRadio.val()) {
        itemSetField.show();
        termsField.hide();
        urisField.hide();
    } else if ('uri' === thisRadio.val().trim()) {
        itemSetField.hide();
        termsField.hide();
        urisField.show();
    } else {
        itemSetField.hide();
        termsField.show();
        urisField.hide();
    }
});

});

$(document).ready(function() {

var propertyList = $('#properties');
var titleProperty = $('#title-property-id');
var descriptionProperty = $('#description-property-id');

var titlePropertyTemplate = $('<span class="title-property-cell">' + Omeka.jsTranslate('Title') + '</span>');
var descriptionPropertyTemplate = $('<span class="description-property-cell">' + Omeka.jsTranslate('Description') + '</span>');

// Mark the title and description properties.
$('#properties li[data-property-id="' + titleProperty.val() + '"] .actions').before(titlePropertyTemplate);
$('#properties li[data-property-id="' + descriptionProperty.val() + '"] .actions').before(descriptionPropertyTemplate);

// Enable sorting on property rows.
new Sortable(propertyList[0], {
    draggable: ".property",
    handle: ".sortable-handle"
});

// Add property row via the property selector.
$('#property-selector .selector-child').click(function(e) {
    e.preventDefault();
    var propertyId = $(this).closest('li').data('property-id');
    $.get(propertyList.data('addNewPropertyRowUrl'), {property_id: propertyId})
        .done(function(data) {
            // Check if the property is the template title or description.
            propertyList.append(data);
            if (propertyId == titleProperty.val()) {
                $('.title-property-cell').remove();
                $('#properties .property[data-property-id=' + propertyId + ']').find('.actions').before(titlePropertyTemplate);
            }
            if (propertyId == descriptionProperty.val()) {
                $('.description-property-cell').remove();
                $('#properties .property[data-property-id=' + propertyId + ']').find('.actions').before(descriptionPropertyTemplate);
            }
        });
});

propertyList.on('click', '.property-remove', function(e) {
    e.preventDefault();
    var thisButton = $(this);
    var prop = thisButton.closest('.property');
    prop.find(':input').prop('disabled', true);
    prop.addClass('delete');
    prop.find('.property-restore').show().focus();
    thisButton.hide();
});

propertyList.on('click', '.property-restore', function(e) {
    e.preventDefault();
    var thisButton = $(this);
    var prop = thisButton.closest('.property');
    prop.find(':input').prop('disabled', false);
    prop.removeClass('delete');
    prop.find('.property-remove').show().focus();
    thisButton.hide();
});

propertyList.on('click', '.property-edit', function(e) {
    e.preventDefault();
    var prop = $(this).closest('.property');
    var propertyId = prop.data('property-id');
    var oriLabel = prop.find('.original-label');
    var altLabel = prop.find('.alternate-label');
    var oriComment = prop.find('.original-comment');
    var altComment = prop.find('.alternate-comment');
    var isRequired = prop.find('.is-required');
    var isPrivate = prop.find('.is-private');
    var dataTypes = prop.find('.data-type');
    var defaultValue = prop.find('.default-value');
    var allowedLanguages = prop.find('.allowed-languages');

    $('#original-label').text(oriLabel.val());
    $('#alternate-label').val(altLabel.val());
    $('#original-comment').text(oriComment.val());
    $('#alternate-comment').val(altComment.val());
    $('#is-title-property').prop('checked', propertyId == titleProperty.val());
    $('#is-description-property').prop('checked', propertyId == descriptionProperty.val());
    $('#is-required').prop('checked', isRequired.val());
    $('#is-private').prop('checked', isPrivate.val());
    $('#data-type').val(dataTypes.val().split(','));
    $('#data-type').trigger('chosen:updated');
    $('#default-value').val(defaultValue.val());
    $('#allowed-languages').val(allowedLanguages.val());

    $('#set-changes').off('click.setchanges').on('click.setchanges', function(e) {
        altLabel.val($('#alternate-label').val());
        prop.find('.alternate-label-cell').text($('#alternate-label').val());
        altComment.val($('#alternate-comment').val());
        // The title and the description of the template will be the first value in the specified property, whatever row.
        if ($('#is-title-property').prop('checked')) {
            titleProperty.val(propertyId);
            $('.title-property-cell').remove();
            $('#properties .property[data-property-id=' + propertyId + ']').find('.actions').before(titlePropertyTemplate);
        } else if (propertyId == titleProperty.val()) {
            titleProperty.val(null);
            $('.title-property-cell').remove();
        }
        if ($('#is-description-property').prop('checked')) {
            descriptionProperty.val(propertyId);
            $('.description-property-cell').remove();
            $('#properties .property[data-property-id=' + propertyId + ']').find('.actions').before(descriptionPropertyTemplate);
        } else if (propertyId == descriptionProperty.val()) {
            descriptionProperty.val(null);
            $('.description-property-cell').remove();
        }
        $('#is-required').prop('checked') ? isRequired.val(1) : isRequired.val(null);
        $('#is-private').prop('checked') ? isPrivate.val(1) : isPrivate.val(null);
        dataTypes.val($('#data-type').val());
        defaultValue.val($('#default-value').val());
        allowedLanguages.val($('#allowed-languages').val());
        Omeka.closeSidebar($('#edit-sidebar'));
    });

    Omeka.openSidebar($('#edit-sidebar'));
});

});

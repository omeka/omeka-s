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

// Update the property data types, that are a list in an hidden input.
$('#resourcetemplateform').on('submit', function(e) {
    propertyList.find('.property.row').each(function() {
        var prop = $(this);
        var dataTypes = prop.find('.data-type').val().split(',').filter(function (el) { return el != ''; });
        var inputName = prop.find('.data-type').attr('name');
        prop.find('.data-type').remove();
        dataTypes.forEach(function(dataType) {
            prop.append('<input type="hidden" name="' + inputName + '" value="' + dataType +'">');
        });
    });
});

// Add property row via the property selector.
$('#property-selector .selector-child').click(function(e) {
    e.preventDefault();
    var propertyId = $(this).closest('li').data('property-id');
    if ($('#properties li[data-property-id="' + propertyId + '"]').length) {
        // Resource templates cannot be assigned duplicate properties.
        return;
    }
    $.get(propertyList.data('addNewPropertyRowUrl'), {property_id: propertyId})
        .done(function(data) {
            propertyList.append(data);
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

    // Get values stored in the row.
    var prop = $(this).closest('.property');
    var propId = prop.data('property-id');
    var oriLabel = prop.find('.original-label');
    var altLabel = prop.find('.alternate-label');
    var oriComment = prop.find('.original-comment');
    var altComment = prop.find('.alternate-comment');
    var isRequired = prop.find('.is-required');
    var isPrivate = prop.find('.is-private');
    var defaultLang = prop.find('.default-lang');
    var dataTypes = prop.find('.data-type');

    // Copy values into the sidebar.
    $('#original-label').text(oriLabel.val());
    $('#alternate-label').val(altLabel.val());
    $('#original-comment').text(oriComment.val());
    $('#alternate-comment').val(altComment.val());
    $('#is-title-property').prop('checked', propId == titleProperty.val());
    $('#is-description-property').prop('checked', propId == descriptionProperty.val());
    $('#is-required').prop('checked', isRequired.val());
    $('#is-private').prop('checked', isPrivate.val());
    $('#default-lang').val(defaultLang.val());
    $('#data-type option').prop('selected', false);
    dataTypes.val().split(',').filter(function (el) { return el != ''; }).forEach(function(selected) {
        $('#data-type option[value="' + selected + '"]').prop('selected', true);
    });
    $('#data-type').trigger('chosen:updated');

    // When the sidebar fieldset is applied, store new values in the row.
    $('#set-changes').off('click.setchanges').on('click.setchanges', function(e) {
        altLabel.val($('#alternate-label').val());
        prop.find('.alternate-label-cell').text($('#alternate-label').val());
        altComment.val($('#alternate-comment').val());
        if ($('#is-title-property').prop('checked')) {
            titleProperty.val(propId);
            $('.title-property-cell').remove();
            prop.find('.actions').before(titlePropertyTemplate);
        } else if (propId == titleProperty.val()) {
            titleProperty.val(null);
            $('.title-property-cell').remove();
        }
        if ($('#is-description-property').prop('checked')) {
            descriptionProperty.val(propId);
            $('.description-property-cell').remove();
            prop.find('.actions').before(descriptionPropertyTemplate);
        } else if (propId == descriptionProperty.val()) {
            descriptionProperty.val(null);
            $('.description-property-cell').remove();
        }
        $('#is-required').prop('checked') ? isRequired.val(1) : isRequired.val(null);
        $('#is-private').prop('checked') ? isPrivate.val(1) : isPrivate.val(null);
        defaultLang.val($('#default-lang').val());
        dataTypes.val($('#data-type').val().join(','));
        Omeka.closeSidebar($('#edit-sidebar'));
    });

    Omeka.openSidebar($('#edit-sidebar'));
});

});

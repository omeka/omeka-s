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
    if ($('#properties li[data-property-id="' + propertyId + '"]').length) {
        // Resource templates cannot be assigned duplicate properties.
        return;
    }
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
    // Get values stored in the row.
    var prop = $(this).closest('.property');
    var propertyId = prop.data('property-id');
    var oriLabel = prop.find('.original-label');
    var altLabel = prop.find('[data-property-key="o:alternate_label"]');
    var oriComment = prop.find('.original-comment');
    var altComment = prop.find('[data-property-key="o:alternate_comment"]');
    var isRequired = prop.find('[data-property-key="o:is_required"]');
    var isPrivate = prop.find('[data-property-key="o:is_private"]');
    var dataType = prop.find('[data-property-key="o:data_type"]');
    var settings = {};
    prop.find('[data-setting-key]').each(function(index, hiddenElement) {
        settings[index] = $(hiddenElement);
    });

    // Copy values into the sidebar.
    $('#edit-sidebar #original-label').text(oriLabel.val());
    $('#edit-sidebar #alternate-label').val(altLabel.val());
    $('#edit-sidebar #original-comment').text(oriComment.val());
    $('#edit-sidebar #alternate-comment').val(altComment.val());
    $('#edit-sidebar #is-title-property').prop('checked', propertyId == titleProperty.val());
    $('#edit-sidebar #is-description-property').prop('checked', propertyId == descriptionProperty.val());
    $('#edit-sidebar #is-required').prop('checked', isRequired.prop('checked'));
    $('#edit-sidebar #is-private').prop('checked', isPrivate.prop('checked'));
    $('#edit-sidebar #data-type option[value="' + dataType.val() + '"]').prop('selected', true);
    $('#edit-sidebar #data-type').trigger('chosen:updated');
    $.each(settings, function(index, hiddenElement) {
        var settingKey = hiddenElement.data('setting-key');
        var sidebarElement = $('#edit-sidebar [data-setting-key="' +  settingKey + '"]');
        var sidebarElementType = sidebarElement.prop('type') ? sidebarElement.prop('type') : sidebarElement.prop('nodeName').toLowerCase();
        if (sidebarElementType === 'checkbox') {
            sidebarElement.prop('checked', hiddenElement.prop('checked'));
        } else if (sidebarElementType === 'radio') {
            $('#edit-sidebar [data-setting-key="' + hiddenElement.data('setting-key') + '"]')
                .val([prop.find('[data-setting-key="' + hiddenElement.data('setting-key') + '"]:checked').val()]);
        } else if (sidebarElementType === 'select' || sidebarElementType === 'select-multiple' ) {
            sidebarElement.val(hiddenElement.val());
            sidebarElement.trigger('chosen:updated');
        } else { // Text, textarea, number…
            sidebarElement.val(hiddenElement.val());
        }
    });

    // When the sidebar fieldset is applied, store new values in the row.
    $('#set-changes').off('click.setchanges').on('click.setchanges', function(e) {
        altLabel.val($('#edit-sidebar #alternate-label').val());
        prop.find('.alternate-label-cell').text($('#edit-sidebar #alternate-label').val());
        altComment.val($('#edit-sidebar #alternate-comment').val());
        if ($('#edit-sidebar #is-title-property').prop('checked')) {
            titleProperty.val(propertyId);
            $('.title-property-cell').remove();
            prop.find('.actions').before(titlePropertyTemplate);
        } else if (propertyId == titleProperty.val()) {
            titleProperty.val(null);
            $('.title-property-cell').remove();
        }
        if ($('#edit-sidebar #is-description-property').prop('checked')) {
            descriptionProperty.val(propertyId);
            $('.description-property-cell').remove();
            prop.find('.actions').before(descriptionPropertyTemplate);
        } else if (propertyId == descriptionProperty.val()) {
            descriptionProperty.val(null);
            $('.description-property-cell').remove();
        }
        isRequired.prop('checked', $('#edit-sidebar #is-required').prop('checked'));
        isPrivate.prop('checked', $('#edit-sidebar #is-private').prop('checked'));
        dataType.val($('#data-type').val());
        // New fields are not yet stored in the row.
        $('#edit-sidebar [data-setting-key]').each(function(index, sidebarElement) {
            sidebarElement = $(sidebarElement);
            var sidebarElementType = sidebarElement.prop('type') ? sidebarElement.prop('type') : sidebarElement.prop('nodeName').toLowerCase();
            var hiddenElement = prop.find('[data-setting-key="' + sidebarElement.data('setting-key') + '"]');
            if (sidebarElementType === 'checkbox') {
                hiddenElement.prop('checked', sidebarElement.prop('checked'));
            } else if (sidebarElementType === 'radio') {
                prop.find('[data-setting-key="' + sidebarElement.data('setting-key') + '"]')
                    .val([$('#edit-sidebar [data-setting-key="' + sidebarElement.data('setting-key') + '"]:checked').val()]);
            } else {
                hiddenElement.val(sidebarElement.val());
            }
        });
        Omeka.closeSidebar($('#edit-sidebar'));
    });

    Omeka.openSidebar($('#edit-sidebar'));
});

});

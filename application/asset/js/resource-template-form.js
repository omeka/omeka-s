$(document).ready(function() {

var propertyList = $('#properties');

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
    var prop = $(this).closest('.property');
    var oriLabel = prop.find('.original-label');
    var altLabel = prop.find('.alternate-label');
    var oriComment = prop.find('.original-comment');
    var altComment = prop.find('.alternate-comment');
    var isRequired = prop.find('.is-required');
    var dataType = prop.find('.data-type');

    $('#original-label').text(oriLabel.val());
    $('#alternate-label').val(altLabel.val());
    $('#original-comment').text(oriComment.val());
    $('#alternate-comment').val(altComment.val());
    $('#is-required').prop('checked', isRequired.val());
    $('#data-type option[value="' + dataType.val() + '"]').prop('selected', true);
    $('#data-type').trigger('chosen:updated');

    $('#set-changes').off('click.setchanges').on('click.setchanges', function(e) {
        altLabel.val($('#alternate-label').val());
        altComment.val($('#alternate-comment').val());
        $('#is-required').prop('checked') ? isRequired.val(1) : isRequired.val(null);
        dataType.val($('#data-type').val());
        Omeka.closeSidebar($('#edit-sidebar'));
    });

    Omeka.openSidebar($('#edit-sidebar'));
});

});

$(document).ready(function() {

var propertyList = $('#properties');

// Enable sorting on property rows.
new Sortable(propertyList[0], {
    draggable: ".property",
    handle: ".sortable-handle"
});

// Add property row via the property selector.
$('#property-selector .selector-child').click(function(event) {
    event.preventDefault();
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

// Remove property via the delete icon.
$('#content').on('click', '.resource-template-property-remove', function(event) {
    event.preventDefault();

    var removeLink = $(this);
    var propertyRow =  removeLink.closest('li.property.row');
    var propertyId = propertyRow.data('property-id');

    // Remove the property ID element from the form.
    var propertyIdElement = propertyRow.children('.property-id-element');
    propertyRow.data('property-id-element', propertyIdElement);
    propertyIdElement.remove();

    // Restore property link.
    var undoRemoveLink = $('<a>', {
        href: '#',
        class: 'fa fa-undo',
        title: Omeka.jsTranslate('Restore property'),
        click: function(event) {
            event.preventDefault();
            propertyRow.toggleClass('delete');
            propertyRow.append(propertyRow.data('property-id-element'));
            removeLink.show();
            $(this).remove();
        },
    });

    propertyRow.toggleClass('delete');
    undoRemoveLink.insertAfter(removeLink);
    removeLink.hide();
});

var setDataTypeOptionsForm = function(dataType) {
    optionsForm = dataTypeOptionsForms[dataType];
    if (optionsForm) {
        $('#data-type-options').html(optionsForm);
    } else {
        $('#data-type-options').empty();
    }
}

var dataTypeOptionsForms = {};
$('#data-type-options-forms > span').each(function() {
    var span = $(this);
    dataTypeOptionsForms[span.data('data-type')] = span.data('options-form')
});

$('#properties').on('click', '.property-edit', function(e) {
    e.preventDefault();

    var sidebar = $('#edit-sidebar');
    Omeka.closeSidebar(sidebar);

    var property = $(this).closest('.property');
    var altLabel = property.find('.alternate-label')
    var altComment = property.find('.alternate-comment')
    var isRequired = property.find('.is-required')
    var dataType = property.find('.data-type')

    $('#alternate-label').val(altLabel.val());
    $('#alternate-comment').val(altComment.val());
    $('#is-required').prop('checked', isRequired.val());
    $('#data-type').val(dataType.val());
    setDataTypeOptionsForm(dataType.val());

    $('#set-changes').off().on('click', function(e) {
        altLabel.val($('#alternate-label').val());
        altComment.val($('#alternate-comment').val());
        $('#is-required').prop('checked') ? isRequired.val(1) : isRequired.val(null);
        dataType.val($('#data-type').val());

        Omeka.closeSidebar(sidebar);
    });

    Omeka.openSidebar(sidebar);
});

$('#data-type').on('change', function(e) {
    setDataTypeOptionsForm($(this).find(':selected').val());
});

});

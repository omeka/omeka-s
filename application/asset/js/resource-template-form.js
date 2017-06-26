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

$('#properties').on('change', '.data-type-select', function(e) {
    var sidebar = $('#data-type-options-sidebar');
    var dataType = $(this).find(':selected');
    var optionsForm = $('#data-type-options-forms > span').filter(function() {
        return $(this).attr('data-data-type') === dataType.val();
    }).data('options-form');
    Omeka.closeSidebar(sidebar);
    $(e.delegateTarget).find('.property').removeClass('selected-data-type');
    if (optionsForm) {
        Omeka.openSidebar(sidebar);
        sidebar.find('.sidebar-content').html(optionsForm);
        dataType.closest('.property').addClass('selected-data-type');
    }
});

});

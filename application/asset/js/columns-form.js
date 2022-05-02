$(document).ready(function() {

const sidebarColumn = $('<div class="sidebar" id="columns-sidebar"><div class="sidebar-content"></div></div>');
sidebarColumn.appendTo('#content');

/**
 * Reset column type select.
 *
 * This ensures that there are no more columns of a type set to this element
 * than is allowed. It does this by disabling columns types that are equal to or
 * exceed the maximum that is set by the column type.
 */
const resetColumnTypeSelect = function(formElement) {
    const columnTypeSelect = formElement.find('.columns-column-type-select');
    const columnAddButton = formElement.find('.columns-column-add-button');
    columnTypeSelect.val('').trigger('chosen:updated');
    columnAddButton.prop('disabled', true);
    columnTypeSelect.find('option').each(function() {
        const thisOption = $(this);
        const columnType = thisOption.val();
        const maxColumns = thisOption.data('maxColumns');
        if (maxColumns) {
            const numColumns = formElement.find(`li[data-column-type="${columnType}"]`).length;
            if (numColumns >= maxColumns) {
                thisOption.prop('disabled', true);
            }
        }
    });
};

// Initiate the columns elements on load.
$('.columns-form-element').each(function() {
    const thisFormElement = $(this);
    const columns = thisFormElement.find('.columns-columns');
    const columnsData = thisFormElement.data('columnsData');
    const columnsLabels = thisFormElement.data('columnsLabels');
    // Enable column sorting.
    new Sortable(columns[0], {draggable: '.columns-column', handle: '.sortable-handle'});
    // Add configured columns to list.
    $.post(thisFormElement.data('columnsUrl'), {
        'resource_type': thisFormElement.data('resourceType'),
        'user_id': thisFormElement.data('userId')
    }, function(data) {
        thisFormElement.find('.columns-columns').html(data);
        resetColumnTypeSelect(thisFormElement);
    });
});

// Handle column type select.
$('.columns-column-type-select').on('change', function(e) {
    const thisSelect = $(this);
    const columnAddButton = thisSelect.closest('.columns-form-element').find('.columns-column-add-button');
    columnAddButton.prop('disabled', ('' === thisSelect.val()) ? true : false);
});

// Handle column add button.
$('.columns-column-add-button').on('click', function(e) {
    const thisButton = $(this);
    const formElement = thisButton.closest('.columns-form-element');
    const columnTypeSelect = formElement.find('.columns-column-type-select');
    $.post(formElement.data('columnUrl'), {
        'resource_type': formElement.data('resourceType'),
        'user_id': formElement.data('userId'),
        'column_data': {
            'type': columnTypeSelect.val()
        }
    }, function(data) {
        formElement.find('.columns-columns').append(data);
        resetColumnTypeSelect(formElement);
    });

});

$('.columns-column-edit-button').on('click', function(e) {
    e.preventDefault();
    // @todo: Open sidebar, populate column form
});

});

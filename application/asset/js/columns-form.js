$(document).ready(function() {

let selectedColumn;
const sidebarColumn = $('<div class="sidebar" id="columns-sidebar"></div>');
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
    columnAddButton.prop('disabled', true);
    columnTypeSelect.val('');
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

/**
 * Open the column edit sidebar.
 */
const openSidebarColumn = function(formElement, column) {
    $.get(formElement.data('columnEditSidebarUrl'), {
        'resource_type': formElement.data('resourceType'),
        'user_id': formElement.data('userId'),
        'column_data': column.data('columnData')
    }, function(data) {
        sidebarColumn.html(data);
        Omeka.openSidebar(sidebarColumn);
    });
};

// Initiate the columns elements on load.
$('.columns-form-element').each(function() {
    const thisFormElement = $(this);
    const columns = thisFormElement.find('.columns-columns');
    // Enable column sorting.
    new Sortable(columns[0], {draggable: '.columns-column', handle: '.sortable-handle'});
    // Add configured columns to list.
    $.get(thisFormElement.data('columnListUrl'), {
        'context': thisFormElement.data('context'),
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
    $.get(formElement.data('columnRowUrl'), {
        'resource_type': formElement.data('resourceType'),
        'user_id': formElement.data('userId'),
        'column_data': {
            'type': columnTypeSelect.val()
        }
    }, function(data) {
        const column = $($.parseHTML(data.trim()));
        formElement.find('.columns-columns').append(column);
        selectedColumn = column;
        openSidebarColumn(formElement, column);
        resetColumnTypeSelect(formElement);
    });
});

// Handle column edit button.
$(document).on('click', '.columns-column-edit-button', function(e) {
    e.preventDefault();
    const thisButton = $(this);
    const column = thisButton.closest('.columns-column');
    const formElement = thisButton.closest('.columns-form-element');
    selectedColumn = column;
    openSidebarColumn(formElement, column);
});

// Handle column remove button.
$(document).on('click', '.columns-column-remove-button', function(e) {
    e.preventDefault();
    const thisButton = $(this);
    const column = thisButton.closest('.columns-column');
    column.addClass('delete');
    column.find('.sortable-handle, .columns-column-label, .columns-column-remove-button, .columns-column-edit-button').hide();
    column.find('.columns-column-restore-button, .columns-column-restore').show();
});

// Handle column restore button.
$(document).on('click', '.columns-column-restore-button', function(e) {
    e.preventDefault();
    const thisButton = $(this);
    const column = thisButton.closest('.columns-column');
    column.removeClass('delete');
    column.find('.sortable-handle, .columns-column-label, .columns-column-remove-button, .columns-column-edit-button').show();
    column.find('.columns-column-restore-button, .columns-column-restore').hide();
});

// Handle column set button.
$(document).on('click', '#columns-column-set-button', function(e) {
    const columnForm = $('#columns-column-form');
    const formElement = selectedColumn.closest('.columns-form-element');
    const columnData = selectedColumn.data('columnData');
    let requiredFieldIncomplete = false;
    // Note that we set the value of the input's "data-column-data-key" attribute
    // as the columnData key and the input's value as its value.
    columnForm.find(':input[data-column-data-key]').each(function() {
        const thisInput = $(this);
        if (thisInput.prop('required') && '' === thisInput.val()) {
            alert(Omeka.jsTranslate('Required field must be completed'));
            requiredFieldIncomplete = true;
            return false;
        }
        columnData[thisInput.data('columnDataKey')] = thisInput.val();
    });
    if (requiredFieldIncomplete) {
        return;
    }
    selectedColumn.data(columnData);
    $.get(formElement.data('columnRowUrl'), {
        'resource_type': formElement.data('resourceType'),
        'user_id': formElement.data('userId'),
        'column_data': columnData
    }, function(data) {
        selectedColumn.replaceWith(data);
        Omeka.closeSidebar(sidebarColumn);
    });
});

// Handle form submission.
$(document).on('submit', 'form', function(e) {
    $('.columns-form-element').each(function() {
        const thisFormElement = $(this);
        const columns = thisFormElement.find('.columns-column:not(.delete)');
        const columnsDataInput = thisFormElement.find('.columns-columns-data');
        const columnsData = [];
        columns.each(function() {
            const thisColumn = $(this);
            columnsData.push(thisColumn.data('columnData'));
        });
        columnsDataInput.val(JSON.stringify(columnsData));
    });
});

});

$(document).ready(function() {

const sidebarColumn = $('<div class="sidebar" id="browse-columns-sidebar"><div class="sidebar-content"></div></div>');
sidebarColumn.appendTo('#content');

/**
 * Reset column type select.
 *
 * This ensures that there are no more columns of a type set to this element
 * than is allowed. It does this by disabling columns types that are equal to or
 * exceed the maximum that is set by the column type.
 */
const resetColumnTypeSelect = function(formElement) {
    const columnTypeSelect = formElement.find('.browse-columns-column-type-select');
    const columnAddButton = formElement.find('.browse-columns-column-add-button');
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

/**
 * Add a column to the list.
 */
const addColumn = function(formElement, defaultHeader, columnData) {
    const column = $($.parseHTML(formElement.data('columnTemplate')));
    column.attr('data-column-type', columnData['type']);
    column.attr('data-column-data', JSON.stringify(columnData));
    column.find('.browse-columns-column-label').text(defaultHeader);
    formElement.find('.browse-columns-columns').append(column);
    resetColumnTypeSelect(formElement);
};

// Initiate the browse columns elements on load.
$('.browse-columns-form-element').each(function() {
    const thisFormElement = $(this);
    const columns = thisFormElement.find('.browse-columns-columns');
    const columnsData = thisFormElement.data('columnsData');
    const defaultHeaders = thisFormElement.data('defaultHeaders');
    // Enable column sorting.
    new Sortable(columns[0], {draggable: '.browse-columns-column', handle: '.sortable-handle'});
    // Add configured columns to list.
    for (let index in columnsData) {
        const columnData = columnsData[index];
        const defaultHeader = defaultHeaders[index];
        addColumn(thisFormElement, defaultHeader, columnData);
    }
});

// Handle column type select.
$('.browse-columns-column-type-select').on('change', function(e) {
    const thisSelect = $(this);
    const columnAddButton = thisSelect.closest('.browse-columns-form-element').find('.browse-columns-column-add-button');
    columnAddButton.prop('disabled', ('' === thisSelect.val()) ? true : false);
});

// Handle column add button.
$('.browse-columns-column-add-button').on('click', function(e) {
    const thisButton = $(this);
    const formElement = thisButton.closest('.browse-columns-form-element');
    const columnTypeSelect = formElement.find('.browse-columns-column-type-select');
    addColumn(formElement, columnTypeSelect.find(':selected').data('defaultHeader'), {
        type: columnTypeSelect.val(),
        default: null,
        header: null,
    });
});

});

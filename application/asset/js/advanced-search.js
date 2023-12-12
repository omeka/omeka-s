$(document).ready(function () {

    // Add a multi-value.
    $(document).on('click', '.multi-value .add-value', function(e) {
        e.preventDefault();
        var fieldContainer = $(this).parents('.field');
        var template = fieldContainer.data('field-template');
        var newValue = $(template);
        newValue.children('input[type="text"]').val(null);
        newValue.children('select').prop('selectedIndex', 0);
        newValue.appendTo(fieldContainer.find('.inputs'));
        newValue.trigger('o:value-created');
    });

    // Remove a multi-value.
    $(document).on('click', '.multi-value .remove-value', function(e) {
        e.preventDefault();
        $(this).closest('.value').remove();
    });

    // Set an index to property values and increment.
    $(document).on('o:value-created', '.value', function(e) {
        var value = $(this);
        value.children(':input').attr('name', function () {
            return this.name.replace(/\[\d\]/, '[' + Omeka.propertySearchIndex + ']');
        });
        Omeka.propertySearchIndex++;
    });

    // Disable query text according to query type.
    $(document).on('change', '.query-type', Omeka.disableQueryTextInput);

    // Clean the query before submitting the form.
    $(document).on('submit', '#advanced-search', function(e) {
        Omeka.cleanSearchQuery($(this));
    });

    $('#content').on('change', '.item-set-select-type', function() {
        const typeSelect = $(this);
        const itemSetSelect = typeSelect.closest('.value').find('.item-set-select');
        if ('not_in' === typeSelect.val()) {
            itemSetSelect.attr('name', 'not_item_set_id[]');
        } else {
            itemSetSelect.attr('name', 'item_set_id[]');
        }
    });

});

$(document).ready(function () {

    function incrementScreenReaderLabels(attribute, element, index) {
        var oldAttribute = element.attr(attribute);
        if (!oldAttribute) {
            return;
        }
        var newAttribute = oldAttribute.replace(/\d+/, parseInt(index) + 1);
        element.attr(attribute, newAttribute);
    }

    function updateAdvancedSearchCount(fieldId, rowClass) {
        var field =  $('#' + fieldId);
        var countSpan = $('#' + fieldId + '-alerts').find('.count');
        var countValue = field.find(rowClass).length;
        countSpan.text(countValue);
    }

    // Add a multi-value.
    $(document).on('click', '.multi-value .add-value', function(e) {
        e.preventDefault();
        var fieldContainer = $(this).parents('.field');
        var fieldId = fieldContainer.attr('id');
        var template = fieldContainer.data('field-template');
        var newValue = $(template);
        var index = fieldContainer.data('index');
        newValue.children('input[type="text"]').val(null);
        newValue.children('select').prop('selectedIndex', 0);
        incrementScreenReaderLabels('aria-label', newValue, index);
        incrementScreenReaderLabels('id', newValue, index);
        newValue.appendTo(fieldContainer.find('.inputs'));
        newValue.trigger('o:value-created');
        updateAdvancedSearchCount(fieldId, '.value');

        index++;
        fieldContainer.data('index', index);
    });

    // Remove a multi-value.
    $(document).on('click', '.multi-value .remove-value', function(e) {
        e.preventDefault();
        var removeButton = $(this);
        var field = removeButton.parents('.field');
        var fieldId = field.attr('id');
        var value = removeButton.parents('.value');
        if (field.find('.value').length > 2) {
            var nextFocusButton = value.next('.value').find('.remove-value');
            nextFocusButton.focus();
        } else {
            field.find('.add-value').focus();
        }
        removeButton.closest('.value').remove();
        updateAdvancedSearchCount(fieldId, '.value');
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

});


$(document).ready(function() {
        // Set up multi-value templates

        $('.multi-value.field').each(function() {
            var field = $(this);
            var value = field.find('.value').first().clone();
            var valueHtml = value.wrap('<div></div>').parent().html();
            field.data('field-template', valueHtml);
        });


        // Add a value.
        $('form').on('click', '.multi-value .add-value', function(e) {
            e.preventDefault();
            var fieldContainer = $(this).parents('.field');
            var template = fieldContainer.data('field-template');
            var newValue = $(template);
            newValue.children('input[type="text"]').val(null);
            newValue.children('select').prop('selectedIndex', 0);
            newValue.appendTo(fieldContainer.find('.inputs'));
            newValue.trigger('o:value-created');
        });
        
        // Remove a value.
        $('form').on('click', '.multi-value .remove-value', function(e) {
            e.preventDefault();
            $(this).closest('.value').remove();
        });
});
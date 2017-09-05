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
            var template = $(this).parents('.field').data('field-template');
            var newValue = $(template);
            newValue.children('input[type="text"]').val(null);
            newValue.children('select').prop('selectedIndex', 0);
            newValue.insertBefore($(this)).trigger('o:value-created');
        });
        
        // Remove a value.
        $('form').on('click', '.multi-value .remove-value', function(e) {
            e.preventDefault();
            $(this).closest('.value').remove();
        });
});
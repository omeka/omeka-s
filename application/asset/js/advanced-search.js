$(document).ready( function() {
    var values = $('#property-queries .value');
    var index = values.length;
    // Add a value.
    $('#advanced-search').on('click', '.add-value', function(e) {
        e.preventDefault();
        var first = values.first();
        var clone = first.clone();
        clone.children('input[type="text"]').val(null).prop('disabled', false);
        clone.children('select').prop('selectedIndex', 0);
        clone.children(':input').attr('name', function () {
            return this.name.replace(/\[\d\]/, '[' + index + ']');
        });
        clone.insertBefore($(this));
        index++;
    });

    // Remove a value.
    $('#advanced-search').on('click', '.remove-value', function(e) {
        e.preventDefault();
        var values = $(this).parents('.inputs').children('.value');
        $(this).parent('.value').remove();
    });

    function disableQueryTextInput() {
        var queryType = $(this);
        var queryText = queryType.siblings('.query-text');
        if (queryType.val() === 'ex' || queryType.val() === 'nex') {
            queryText.prop('disabled', true);
        } else {
            queryText.prop('disabled', false);
        }
    }

    $('#advanced-search').find('.query-type').each(disableQueryTextInput);
    $('#advanced-search').on('change', '.query-type', disableQueryTextInput);
});

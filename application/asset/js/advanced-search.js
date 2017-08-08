$(document).ready( function() {
    var values = $('#property-queries .value');
    var itemSets = $('#item-sets .value');
    var index = values.length;

    // Avoid duplicate functionality on admin side.
    $('form').unbind();

    // Add a value.
    $('#property-queries').on('click', '.add-value', function(e) {
        e.preventDefault();
        e.stopPropagation();
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

    // Add a value.
    $('#item-sets').on('click', '.add-value', function(e) {
        e.preventDefault();
        var first = itemSets.first();
        var clone = first.clone();
        clone.children('select').prop('selectedIndex', 0);
        clone.insertBefore($(this));
    });

    // Remove a value.
    $('form').on('click', '.multi-value .remove-value', function(e) {
        e.preventDefault();
        $(this).closest('.value').remove();
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

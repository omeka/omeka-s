$(document).ready( function() {
    var values = $('#property-queries .value');
    var itemSets = $('#item-sets .value');
    var index = values.length;

    // Add a value.
    $('#property-queries').on('o:value-created', '.value', function(e) {
        var value = $(this);
        value.children(':input').attr('name', function () {
            return this.name.replace(/\[\d\]/, '[' + index + ']');
        });
        index++;
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

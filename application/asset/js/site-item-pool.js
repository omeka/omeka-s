(function ($) {
        /**
     * Extract the item pool query
     */
    function extractItemPoolQuery() {
        var query = {};

        // Handle the resource class
        var resourceClassId = $('#advanced-search select[name="resource_class_id"]').val();
        if (resourceClassId) {
            query['resource_class_id'] = Number(resourceClassId);
        }

        // Handle the item sets
        $('#item-sets').find('select[name="item_set_id[]"] option:selected').each(function(index) {
            var itemSetId = $(this).val();
            if (itemSetId) {
                if (!query.hasOwnProperty('item_set_id')) {
                    query['item_set_id'] = [];
                }
                query['item_set_id'].push(Number(itemSetId));
            }
        });

        // Handle the property queries
        $('#property-queries').find('.value').each(function(index) {
            var textVal = $(this).children('.query-text').val();
            if (!$.trim(textVal)) {
                return; // do not process an empty query
            }
            var propertyVal = $(this).children('.query-property').val();
            if (!$.isNumeric(propertyVal)) {
                propertyVal = 0;
            }
            if (!query.hasOwnProperty('property')) {
                query['property'] = {};
            }
            if (!query.property.hasOwnProperty(propertyVal)) {
                query.property[propertyVal] = {};
            }
            var typeVal = $(this).children('.query-type').val();
            if (!query.property[propertyVal].hasOwnProperty(typeVal)) {
                query.property[propertyVal][typeVal] = [];
            }
            query.property[propertyVal][typeVal].push(textVal);
        });

        // Handle the has_property queries
        $('#has-property-queries').find('.value').each(function(index) {
            var propertyVal = $(this).children('.query-property').val();
            if (!$.isNumeric(propertyVal)) {
                return; // do not process an invalid property
            }
            if (!query.hasOwnProperty('has_property')) {
                query['has_property'] = {};
            }
            var typeVal = $(this).children('.query-type').val();
            query.has_property[propertyVal] = Number(typeVal);
        });

        return JSON.stringify(query);
    }

    $(document).ready(function () {
        var originalQuery = extractItemPoolQuery();
        $('#site-form').on('o:before-form-unload', function () {
            if (originalQuery !== extractItemPoolQuery()) {
                Omeka.markDirty(this);
            }
        });
        $('#site-form').on('submit', function(e) {
            // Append the query object to the form
            $('<input>', {type: 'hidden', name: 'item_pool'})
                .val(extractItemPoolQuery())
                .appendTo('#site-form');
        });
    });
})(jQuery);

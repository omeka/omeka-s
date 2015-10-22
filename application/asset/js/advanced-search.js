$(document).ready( function() {

// Remove all names from query form elements.
$('.query-type, .query-text, .query-property').attr('name', null);

// Add a value.
$('#advanced-search').on('click', '.add-value', function(e) {
    e.preventDefault();
    var first = $(this).parents('.field').find('.value').first();
    var clone = first.clone();
    clone.children('input[type="text"]').val(null);
    clone.children('select').prop('selectedIndex', 0);
    clone.insertBefore($(this));
});

// Remove a value.
$('#advanced-search').on('click', '.remove-value', function(e) {
    e.preventDefault();
    var values = $(this).parents('.inputs').children('.value');
    $(this).parent('.value').remove();
});

// Bypass regular form handling for value, property, and has property queries.
$('#advanced-search').submit(function(event) {

    $('#value-queries').find('.value').each(function(index) {
        var text = $(this).children('.query-text');
        if (!$.trim(text.val())) {
            return; // do not process an empty query
        }
        var type = $(this).children('.query-type');
        $('<input>').attr('type', 'hidden')
            .attr('name', 'value[' + type.val() + '][]')
            .val(text.val())
            .appendTo('#advanced-search');
    });

    $('#property-queries').find('.value').each(function(index) {
        var text = $(this).children('.query-text');
        if (!$.trim(text.val())) {
            return; // do not process an empty query
        }
        var property = $(this).children('.query-property');
        if (!$.isNumeric(property.val())) {
            return; // do not process an invalid property
        }
        var type = $(this).children('.query-type');
        $('<input>').attr('type', 'hidden')
            .attr('name', 'property[' + property.val() + '][' + type.val() + '][]')
            .val(text.val())
            .appendTo('#advanced-search');
    });

    $('#has-property-queries').find('.value').each(function(index) {
        var property = $(this).children('.query-property');
        if (!$.isNumeric(property.val())) {
            return; // do not process an invalid property
        }
        var type = $(this).children('.query-type');
        $('<input>').attr('type', 'hidden')
            .attr('name', 'has_property[' + property.val() + ']')
            .val(type.val())
            .appendTo('#advanced-search');
    });
});

/**
 * Extract the item pool query
 */
$('#site-form').on('submit', function(e) {
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

    // Handle the value queries
    $('#value-queries').find('.value').each(function(index) {
        var textVal = $(this).children('.query-text').val();
        if (!$.trim(textVal)) {
            return; // do not process an empty query
        }
        if (!query.hasOwnProperty('value')) {
            query['value'] = {};
        }
        var typeVal = $(this).children('.query-type').val();
        if (!query.value.hasOwnProperty(typeVal)) {
            query.value[typeVal] = [];
        }
        query.value[typeVal].push(textVal);
    });

    // Handle the property queries
    $('#property-queries').find('.value').each(function(index) {
        var textVal = $(this).children('.query-text').val();
        if (!$.trim(textVal)) {
            return; // do not process an empty query
        }
        var propertyVal = $(this).children('.query-property').val();
        if (!$.isNumeric(propertyVal)) {
            return; // do not process an invalid property
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

    // Append the query object to the form
    $('<input>', {type: 'hidden', name: 'item_pool'})
        .val(JSON.stringify(query))
        .appendTo('#site-form');
});

});

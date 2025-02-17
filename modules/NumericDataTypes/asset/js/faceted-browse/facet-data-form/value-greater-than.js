FacetedBrowse.registerFacetAddEditHandler('value_greater_than', function() {
    $('#value-greater-than-property-id').chosen({
        allow_single_deselect: true,
    });
});
FacetedBrowse.registerFacetSetHandler('value_greater_than', function() {
    const propertyId = $('#value-greater-than-property-id');
    const min = $('#value-greater-than-min');
    const max = $('#value-greater-than-max');
    const step = $('#value-greater-than-step');
    if (!propertyId.val()) {
        alert(Omeka.jsTranslate('A facet must have a property.'));
    } else {
        return {
            property_id: propertyId.val(),
            min: min.val(),
            max: max.val(),
            step: step.val(),
            values: $('#date-after-values').val()
        };
    }
});

$(document).ready(function() {

// Clear show all during certain interactions.
$(document).on('change', '#value-greater-than-property-id', function(e) {
    $('#show-all').prop('checked', false);
    $('#show-all-table-container').empty();
});

});

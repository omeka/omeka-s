FacetedBrowse.registerFacetAddEditHandler('value_less_than', function() {
    $('#value-less-than-property-id').chosen({
        allow_single_deselect: true,
    });
});
FacetedBrowse.registerFacetSetHandler('value_less_than', function() {
    const propertyId = $('#value-less-than-property-id');
    const min = $('#value-less-than-min');
    const max = $('#value-less-than-max');
    const step = $('#value-less-than-step');
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
$(document).on('change', '#value-less-than-property-id', function(e) {
    $('#show-all').prop('checked', false);
    $('#show-all-table-container').empty();
});

});

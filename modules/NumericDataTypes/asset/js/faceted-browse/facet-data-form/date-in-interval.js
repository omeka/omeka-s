FacetedBrowse.registerFacetAddEditHandler('date_in_interval', function() {
    $('#date-in-interval-property-id').chosen({
        allow_single_deselect: true,
    });
});
FacetedBrowse.registerFacetSetHandler('date_in_interval', function() {
    const propertyId = $('#date-in-interval-property-id');
    if (!propertyId.val()) {
        alert(Omeka.jsTranslate('A facet must have a property.'));
    } else {
        return {
            property_id: propertyId.val(),
            values: $('#date-in-interval-values').val()
        };
    }
});

$(document).ready(function() {

// Clear show all during certain interactions.
$(document).on('change', '#date-in-interval-property-id', function(e) {
    $('#show-all').prop('checked', false);
    $('#show-all-table-container').empty();
});

});

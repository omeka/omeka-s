FacetedBrowse.registerFacetAddEditHandler('date_after', function() {
    $('#date-after-property-id').chosen({
        allow_single_deselect: true,
    });
});
FacetedBrowse.registerFacetSetHandler('date_after', function() {
    const propertyId = $('#date-after-property-id');
    if (!propertyId.val()) {
        alert(Omeka.jsTranslate('A facet must have a property.'));
    } else {
        return {
            property_id: propertyId.val(),
            values: $('#date-after-values').val()
        };
    }
});

$(document).ready(function() {

// Clear show all during certain interactions.
$(document).on('change', '#date-after-property-id', function(e) {
    $('#show-all').prop('checked', false);
    $('#show-all-table-container').empty();
});

});

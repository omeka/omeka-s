FacetedBrowse.registerFacetAddEditHandler('date_before', function() {
    $('#date-before-property-id').chosen({
        allow_single_deselect: true,
    });
});
FacetedBrowse.registerFacetSetHandler('date_before', function() {
    const propertyId = $('#date-before-property-id');
    if (!propertyId.val()) {
        alert(Omeka.jsTranslate('A facet must have a property.'));
    } else {
        return {
            property_id: propertyId.val(),
            values: $('#date-before-values').val()
        };
    }
});

$(document).ready(function() {

// Clear show all during certain interactions.
$(document).on('change', '#date-before-property-id', function(e) {
    $('#show-all').prop('checked', false);
    $('#show-all-table-container').empty();
});

});

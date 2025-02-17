FacetedBrowse.registerFacetAddEditHandler('duration_greater_than', function() {
    $('#duration-greater-than-property-id').chosen({
        allow_single_deselect: true,
    });
});
FacetedBrowse.registerFacetSetHandler('duration_greater_than', function() {
    const propertyId = $('#duration-greater-than-property-id');
    if (!propertyId.val()) {
        alert(Omeka.jsTranslate('A facet must have a property.'));
    } else {
        return {
            property_id: propertyId.val(),
            values: $('#duration-greater-than-values').val()
        };
    }
});

$(document).ready(function() {

// Clear show all during certain interactions.
$(document).on('change', '#duration-greater-than-property-id', function(e) {
    $('#show-all').prop('checked', false);
    $('#show-all-table-container').empty();
});

});

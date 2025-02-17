FacetedBrowse.registerFacetApplyStateHandler('duration_greater_than', function(facet, facetState) {
    const thisFacet = $(facet);
    thisFacet.find(`select.duration-greater-than-value`).val(facetState);
});

$(document).ready(function() {

const container = $('#container');

container.on('change', '.duration-greater-than-value', function(e) {
    const thisSelect = $(this);
    const facet = thisSelect.closest('.facet');
    const facetData = facet.data('facetData');
    const query = thisSelect.val()
    ? `numeric[dur][gt][pid]=${facetData.property_id}&numeric[dur][gt][val]=${encodeURIComponent(thisSelect.val())}`
    : '';
    FacetedBrowse.setFacetState(facet.data('facetId'), thisSelect.val(), query);
    FacetedBrowse.triggerStateChange();
});

});

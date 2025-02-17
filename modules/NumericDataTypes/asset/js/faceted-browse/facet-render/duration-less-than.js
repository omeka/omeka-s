FacetedBrowse.registerFacetApplyStateHandler('duration_less_than', function(facet, facetState) {
    const thisFacet = $(facet);
    thisFacet.find(`select.duration-less-than-value`).val(facetState);
});

$(document).ready(function() {

const container = $('#container');

container.on('change', '.duration-less-than-value', function(e) {
    const thisSelect = $(this);
    const facet = thisSelect.closest('.facet');
    const facetData = facet.data('facetData');
    const query = thisSelect.val()
    ? `numeric[dur][lt][pid]=${facetData.property_id}&numeric[dur][lt][val]=${encodeURIComponent(thisSelect.val())}`
    : '';
    FacetedBrowse.setFacetState(facet.data('facetId'), thisSelect.val(), query);
    FacetedBrowse.triggerStateChange();
});

});

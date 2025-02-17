FacetedBrowse.registerFacetApplyStateHandler('date_in_interval', function(facet, facetState) {
    const thisFacet = $(facet);
    thisFacet.find(`select.date-in-interval-value`).val(facetState);
});

$(document).ready(function() {

const container = $('#container');

container.on('change', '.date-in-interval-value', function(e) {
    const thisSelect = $(this);
    const facet = thisSelect.closest('.facet');
    const facetData = facet.data('facetData');
    const query = thisSelect.val()
        ? `numeric[ivl][pid]=${facetData.property_id}&numeric[ivl][val]=${encodeURIComponent(thisSelect.val())}`
        : '';
    FacetedBrowse.setFacetState(facet.data('facetId'), thisSelect.val(), query);
    FacetedBrowse.triggerStateChange();
});

});

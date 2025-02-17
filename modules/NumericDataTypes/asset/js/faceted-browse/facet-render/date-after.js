FacetedBrowse.registerFacetApplyStateHandler('date_after', function(facet, facetState) {
    const thisFacet = $(facet);
    thisFacet.find(`select.date-after-value`).val(facetState);
});

$(document).ready(function() {

const container = $('#container');

container.on('change', '.date-after-value', function(e) {
    const thisSelect = $(this);
    const facet = thisSelect.closest('.facet');
    const facetData = facet.data('facetData');
    const query = thisSelect.val()
        ? `numeric[ts][gt][pid]=${facetData.property_id}&numeric[ts][gt][val]=${encodeURIComponent(thisSelect.val())}`
        : '';
    FacetedBrowse.setFacetState(facet.data('facetId'), thisSelect.val(), query);
    FacetedBrowse.triggerStateChange();
});

});

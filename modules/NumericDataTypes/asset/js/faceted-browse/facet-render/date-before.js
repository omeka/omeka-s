FacetedBrowse.registerFacetApplyStateHandler('date_before', function(facet, facetState) {
    const thisFacet = $(facet);
    thisFacet.find(`select.date-before-value`).val(facetState);
});

$(document).ready(function() {

const container = $('#container');

container.on('change', '.date-before-value', function(e) {
    const thisSelect = $(this);
    const facet = thisSelect.closest('.facet');
    const facetData = facet.data('facetData');
    const query = thisSelect.val()
        ? `numeric[ts][lt][pid]=${facetData.property_id}&numeric[ts][lt][val]=${encodeURIComponent(thisSelect.val())}`
        : '';
    FacetedBrowse.setFacetState(facet.data('facetId'), thisSelect.val(), query);
    FacetedBrowse.triggerStateChange();
});

});

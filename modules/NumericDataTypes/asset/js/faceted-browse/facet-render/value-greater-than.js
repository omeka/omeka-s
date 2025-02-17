FacetedBrowse.registerFacetApplyStateHandler('value_greater_than', function(facet, facetState) {
    const thisFacet = $(facet);
    const thisInput = thisFacet.find(`.value-greater-than`);
    thisInput.val(facetState);
});

$(document).ready(function() {

const container = $('#container');

container.on('input', '.value-greater-than', function(e) {
    const thisInput = $(this);
    const facet = thisInput.closest('.facet');
    const facetData = facet.data('facetData');
    FacetedBrowse.setFacetState(
        facet.data('facetId'),
        thisInput.val(),
        `numeric[int][gt][pid]=${facetData.property_id}&numeric[int][gt][val]=${encodeURIComponent(thisInput.val())}`
    );
    FacetedBrowse.triggerStateChange();
});

});

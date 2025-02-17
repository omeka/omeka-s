FacetedBrowse.registerFacetApplyStateHandler('value_less_than', function(facet, facetState) {
    const thisFacet = $(facet);
    const thisInput = thisFacet.find(`.value-less-than`);
    thisInput.val(facetState);
});

$(document).ready(function() {

const container = $('#container');

container.on('input', '.value-less-than', function(e) {
    const thisInput = $(this);
    const facet = thisInput.closest('.facet');
    const facetData = facet.data('facetData');
    FacetedBrowse.setFacetState(
        facet.data('facetId'),
        thisInput.val(),
        `numeric[int][lt][pid]=${facetData.property_id}&numeric[int][lt][val]=${encodeURIComponent(thisInput.val())}`
    );
    FacetedBrowse.triggerStateChange();
});

});

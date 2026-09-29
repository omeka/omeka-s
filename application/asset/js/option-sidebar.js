$(document).ready(function() {

/**
 * Filter the options of an "add" sidebar.
 *
 * Rows hidden by the sidebar's arrangement use the hidden attribute and stay
 * hidden; rows that don't match the filter get the "filtered" class. The
 * Pinned section is hidden while filtering so matches aren't listed twice.
 */
const filterOptions = function(sidebar, query) {
    query = query.trim().toLowerCase();
    let matchCount = 0;
    sidebar.find('.option-sidebar-group:not(.option-sidebar-pinned)').each(function() {
        const group = $(this);
        let groupHasVisibleRows = false;
        group.find('.option-sidebar-row').each(function() {
            const row = $(this);
            const text = (row.data('label') + ' ' + row.data('module')).toLowerCase();
            const isMatch = '' === query || text.includes(query);
            row.toggleClass('filtered', !isMatch);
            if (isMatch && !row.prop('hidden')) {
                groupHasVisibleRows = true;
                matchCount++;
            }
        });
        group.prop('hidden', !groupHasVisibleRows);
    });
    sidebar.find('.option-sidebar-pinned').prop('hidden', '' !== query);
    sidebar.find('.option-sidebar-no-matches').prop('hidden', '' === query || 0 < matchCount);

    const announcer = sidebar.find('.option-sidebar-announcer');
    announcer.text('' === query ? '' : announcer.data('matchesMessage').replace('%s', matchCount));
};

$(document).on('input', '.option-sidebar-filter', function() {
    const filter = $(this);
    filterOptions(filter.closest('.option-sidebar'), filter.val());
});

// The media sidebar sits inside the item form, where Enter in the filter would
// submit the item.
$(document).on('keydown', '.option-sidebar-filter', function(e) {
    if ('Enter' === e.key) {
        e.preventDefault();
    }
});

});

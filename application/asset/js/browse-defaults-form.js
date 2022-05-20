$(document).ready(function() {

    // Initiate the browse defaults elements on load.
    $('.browse-defualts-form-element').each(function() {
        const thisFormElement = $(this);
        const browseDefaultsInput = thisFormElement.find('input.browse-defaults');
        const sortBySelect = thisFormElement.find('.browse-defualts-sort-by');
        const sortByCustomInput = thisFormElement.find('.browse-defualts-sort-by-custom');
        const sortOrderSelect = thisFormElement.find('.browse-defualts-sort-order');

        const browseDefaults = JSON.parse(browseDefaultsInput.val());
        const sortBy = browseDefaults[0];
        const sortOrder = browseDefaults[1];

        const sortByOption = sortBySelect.find(`option[value="${sortBy}"]`);
        if (sortByOption.length) {
            sortByOption.prop('selected', true);
        } else {
            sortByCustomInput.val(sortBy);
        }
        sortOrderSelect.find(`option[value="${sortOrder}"]`).prop('selected', true);
    });

    // Handle form submission.
    $(document).on('submit', 'form', function(e) {
        const thisForm = $(this);
        $('.browse-defualts-form-element').each(function() {
            const thisFormElement = $(this);
            const browseDefaultsInput = thisFormElement.find('input.browse-defaults');
            const sortBySelect = thisFormElement.find('.browse-defualts-sort-by');
            const sortByCustomInput = thisFormElement.find('.browse-defualts-sort-by-custom');
            const sortOrderSelect = thisFormElement.find('.browse-defualts-sort-order');

            let sortBy = sortByCustomInput.val().trim();
            if (0 === sortBy.length) {
                sortBy = sortBySelect.val();
            }
            const browseDefaults = [sortBy, sortOrderSelect.val(), 1];
            browseDefaultsInput.val(JSON.stringify(browseDefaults));
        });
    });

});

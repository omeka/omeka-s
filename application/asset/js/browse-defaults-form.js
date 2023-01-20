$(document).ready(function() {

    // Initiate the browse defaults elements on load.
    $('.browse-defualts-form-element').each(function() {
        const thisFormElement = $(this);
        const browseDefaultsInput = thisFormElement.find('input.browse-defaults');
        const sortBySelect = thisFormElement.find('.browse-defualts-sort-by');
        const customSortByInput = thisFormElement.find('.browse-defualts-custom-sort-by');
        const sortOrderSelect = thisFormElement.find('.browse-defualts-sort-order');

        const browseDefaults = JSON.parse(browseDefaultsInput.val());
        const sortBy = browseDefaults.sort_by;
        const sortOrder = browseDefaults.sort_order;

        const sortByOption = sortBySelect.find(`option[value="${sortBy}"]`);
        if (sortByOption.length) {
            sortByOption.prop('selected', true);
        } else {
            customSortByInput.val(sortBy);
            customSortByInput.show();
        }
        sortOrderSelect.find(`option[value="${sortOrder}"]`).prop('selected', true);
    });

    // Handle sort by change.
    $(document).on('change', '.browse-defualts-sort-by', function(e) {
        const thisSelect = $(this);
        const formElement = thisSelect.closest('.browse-defualts-form-element');
        const customSortByInput = formElement.find('.browse-defualts-custom-sort-by');
        if ('' === thisSelect.val()) {
            customSortByInput.show();
        } else {
            customSortByInput.hide();
            customSortByInput.val('');
        }
    });

    // Handle form submission.
    $(document).on('submit', 'form', function(e) {
        $('.browse-defualts-form-element').each(function() {
            const thisFormElement = $(this);
            const browseDefaultsInput = thisFormElement.find('input.browse-defaults');
            const sortBySelect = thisFormElement.find('.browse-defualts-sort-by');
            const customSortByInput = thisFormElement.find('.browse-defualts-custom-sort-by');
            const sortOrderSelect = thisFormElement.find('.browse-defualts-sort-order');

            let sortBy = customSortByInput.val().trim();
            if (0 === sortBy.length) {
                sortBy = sortBySelect.val();
            }
            const browseDefaults = {
                sort_by: sortBy,
                sort_order: sortOrderSelect.val()
            };
            browseDefaultsInput.val(JSON.stringify(browseDefaults));
        });
    });

});

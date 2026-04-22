(function ($) {
    $(document).ready(function () {
        // Track which picker element is currently open in the sidebar.
        var selectingElement = null;
        // One shared sidebar instance is reused across all pickers on the page.
        var sidebar = $('<div class="sidebar" id="resource-picker-sidebar"><div class="sidebar-content"></div></div>');
        sidebar.appendTo('#content');

        // Clear the selecting element when the sidebar is closed via the close button.
        sidebar.on('o:sidebar-closed', function () {
            selectingElement = null;
        });

        // Open the sidebar and load the resource picker for the clicked button.
        $('#content').on('click', '.resource-picker-select-button', function () {
            selectingElement = $(this).closest('.resource-picker-select-element');
            Omeka.openSidebar(sidebar);
            Omeka.populateSidebarContent(sidebar, $(this).data('sidebar-content-url'));
        });

        // Re-fetch sidebar content using the search URL and all current input
        // values, including hidden inputs that carry extra query params.
        function searchSidebar() {
            var searchContainer = sidebar.find('.resource-search');
            Omeka.populateSidebarContent(sidebar, searchContainer.data('search-url'), searchContainer.find(':input').serialize());
        }

        sidebar.on('click', '.resource-picker-search-button', function () {
            searchSidebar();
        });

        // Allow submitting the search form by pressing Enter in the search input.
        sidebar.on('keydown', '[name="search"]', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchSidebar();
            }
        });

        // Initialize Chosen on any select elements loaded into the sidebar,
        // then mark any resources that are already selected in the widget.
        sidebar.on('o:sidebar-content-loaded', function () {
            var newOptions = $.extend({}, chosenOptions);
            newOptions.include_group_label_in_selected = false;
            sidebar.find('.chosen-select').chosen(newOptions);
            markSidebarSelections();
        });

        // Mark sidebar resource links as already-selected if their ID is present
        // in the current widget selections. Called after sidebar loads or changes.
        function markSidebarSelections() {
            if (!selectingElement) return;
            var selectedIds = selectingElement
                .find('.resource-picker-selections input[type="hidden"]')
                .map(function () { return String($(this).val()); })
                .get();
            sidebar.find('.resource-picker-select-resource').each(function () {
                $(this).toggleClass('already-selected', selectedIds.indexOf(String($(this).data('resource-id'))) !== -1);
            });
        }

        // Handle clicking a resource in the sidebar list.
        // Builds a selection row from the resource's id and pre-rendered HTML,
        // then appends (or replaces) it in the picker's selections list.
        $('#content').on('click', '.resource-picker-select-resource', function (e) {
            e.preventDefault();
            var resourceId = $(this).data('resource-id');
            var resourceHtml = $(this).data('resource-html');
            var inputName = selectingElement.data('input-name');
            var isMultiple = selectingElement.hasClass('resource-picker-select-multiple');

            // For multi-select pickers, skip resources already in the list.
            if (isMultiple) {
                var alreadySelected = selectingElement
                    .find('.resource-picker-selections input[type="hidden"]')
                    .filter(function () { return $(this).val() == resourceId; })
                    .length > 0;
                if (alreadySelected) {
                    return;
                }
            }

            // Build the selection row: resource display, hidden id input, remove button.
            var resource = $('<div class="resource">').append($('<div class="resource-link">').append(resourceHtml));
            resource
                .append($('<input type="hidden">').attr('name', inputName).val(resourceId))
                .append($('<a href="#" class="resource-picker-remove-resource o-icon-delete">').attr('title', Omeka.jsTranslate('Remove')));

            if (isMultiple) {
                selectingElement.find('.resource-picker-selections').append(resource);
                markSidebarSelections();
            } else {
                // Single-select: replace any existing selection and close the sidebar.
                selectingElement.find('.resource-picker-selections').html(resource);
                Omeka.closeSidebar(sidebar);
                selectingElement = null;
            }
        });

        // Remove an individual resource from the selections list.
        $('#content').on('click', '.resource-picker-remove-resource', function (e) {
            e.preventDefault();
            $(this).closest('.resource').remove();
            markSidebarSelections();
        });

        // Clear all selections from a picker.
        $('#content').on('click', '.resource-picker-clear', function () {
            $(this).closest('.resource-picker-select-element').find('.resource-picker-selections').empty();
            markSidebarSelections();
        });
    });
})(jQuery);

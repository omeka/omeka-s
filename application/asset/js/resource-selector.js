(function($) {

    function searchResources() {
        var searchInput = $('#resource-list-search');
        var data = {'search': searchInput.val()};
        var classId = $('#filter-resource-class').val();
        var itemSetId = $('#filter-item-set').val();

        if (classId) {
            data['resource_class_id'] = classId;
        }
        if (itemSetId) {
            data['item_set_id'] = itemSetId;
        }

        Omeka.populateSidebarContent(
            searchInput.closest('.sidebar'),
            searchInput.siblings('button').data('search-url'),
            data
        );
    }

    $(document).ready( function() {
        $('#select-resource').on('keydown', '.pagination input', function (e) {
            if ((e.keycode || e.which) == '13') {
                e.preventDefault();
                Omeka.populateSidebarContent($(this).closest('.sidebar'), $(this).data('paginationUrl'), $(this).serialize());
            }
        });

        $('#select-resource').on('click', '#sidebar-resource-search .o-icon-search', function () {
            searchResources();
        });

        $('#select-resource').on('focus', '#resource-list-search', function() {
            $('#resource-list-search').keydown(function(e) {
                if ((e.keycode || e.which) == '13') {
                    e.preventDefault();
                    searchResources();
                }
            });
        });

        $('#select-item a').on('click', function (e) {
            e.preventDefault()
            Omeka.closeSidebar($('#resource-details'));
            $(this).trigger('o:resource-selected');
        });

        $('#select-resource').on('click', '.select-resource', function(e) {
            e.preventDefault();
            if ($('#item-results').hasClass('active')) {
                var selectCheckbox = $(this).parents('.item.resource').find('.select-resource-checkbox');
                if (selectCheckbox.prop('checked')) {
                    selectCheckbox.prop('checked', false);
                } else {
                    selectCheckbox.prop('checked', true);
                }
            } else {
                Omeka.closeSidebar($(e.delegateTarget));
                $(this).trigger('o:resource-selected');
            }
        });

        $('#select-resource').on('click', '.quick-select-toggle', function() {
            $(this).toggleClass('active');
            $('#item-results').toggleClass('active');
        });

        $('#select-resource').on('click', '.select-resources-button', function(e) {
            Omeka.closeSidebar($(e.delegateTarget));
            $(this).trigger('o:resources-selected');
        });

        $('#select-resource').on('o:sidebar-content-loaded', function(e) {
            // Make a shallow copy of the Chosen options so we can modify it
            // without affecting subsequent Chosen instances.
            var newOptions = $.extend({}, chosenOptions);
            $('#filter-resource-class').chosen(newOptions);
            // Group labels are too long for the item set select.
            newOptions.include_group_label_in_selected = false;
            $('#filter-item-set').chosen(newOptions);
        });

    });
})(jQuery);


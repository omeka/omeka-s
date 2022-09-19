(function($) {

    function searchResources() {
        var sidebarResourceSearch = $('#sidebar-resource-search');
        Omeka.populateSidebarContent(
            sidebarResourceSearch.closest('.sidebar'),
            sidebarResourceSearch.data('search-url'),
            sidebarResourceSearch.find(':input').serialize()
        );
    }

    function handleScreenReaderStatuses(selector, activeState) {
        var successStatuses = $(selector + '.success-statuses');
        if (activeState == true) {
            $(selector + '.success-statuses').addClass('active').find('.on').focus();
        } else {
            $(selector + '.success-statuses').removeClass('active').find('.off').focus();
        }
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
            e.preventDefault();
            Omeka.closeSidebar($('#select-resource'));
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
                $(this).trigger('o:resource-selected');
            }
        });

        $('#select-resource').on('click', '.quick-select-toggle', function() {
            $(this).toggleClass('active').find('button .sr-only').toggleClass('active');
            $('#item-results').toggleClass('active').toggleClass('confirm-main');
            var activeState = $('.quick-select-toggle').hasClass('active');
            handleScreenReaderStatuses('.quick-select', activeState);
        });

        $('#select-resource').on('click', '.select-all', function() {
            $(this).toggleClass('active').find('button .sr-only').toggleClass('active');
            if (!$('#item-results').hasClass('confirm-main')) {
                $('#select-resource .quick-select-toggle').click();
            }
            if ($('.select-resource-checkbox:not(:checked)').length > 0) {
                $('#select-resource .select-resource-checkbox').prop('checked', true);
                var activeState = true;
            } else {
                $('#select-resource .select-resource-checkbox').prop('checked', false);
                var activeState = false;
            }
            handleScreenReaderStatuses('.select-all', activeState);
        });

        $('#select-resource').on('change', '.select-resource-checkbox', function() {
            if ($('.select-resource-checkbox:not(:checked)').length > 0) {
                var activeState = ($('.select-resource-checkbox:not(:checked)').length > 0);
                $('#select-resource button.select-all').removeClass('active');
            } else if (!$('#select-resource button.select-all').hasClass('active')) {
                $('#select-resource button.select-all').addClass('active');
            }
        });

        $('#select-resource').on('click', '.select-resources-button', function(e) {
            Omeka.closeSidebar($(e.delegateTarget));
            $(this).trigger('o:resources-selected');
        });

        $('#select-resource').on('o:sidebar-content-loaded', function(e) {
            // Make a shallow copy of the Chosen options so we can modify it
            // without affecting subsequent Chosen instances.
            var newOptions = $.extend({}, chosenOptions);
            // Group labels are too long for sidebar selects.
            newOptions.include_group_label_in_selected = false;
            $('#filter-resource-class').chosen(newOptions);
            $('#filter-item-set').chosen(newOptions);
        });

    });
})(jQuery);


(function($) {

    function searchResources() {
        var sidebarResourceSearch = $('#sidebar-resource-search');
        Omeka.populateSidebarContent(
            sidebarResourceSearch.closest('.sidebar'),
            sidebarResourceSearch.data('search-url'),
            sidebarResourceSearch.find(':input').serialize()
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
            $(this).toggleClass('active');
            $('#item-results').toggleClass('active').toggleClass('confirm-main');
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

        // Allow to create a new resource in a modal window during edition of another resource.
        var modal;
        $(document).on('click', '.quick-add-resource', function(e) {
            e.preventDefault();
            // Save the modal in local storage to allow recursive new resources.
            var d = new Date();
            var windowName = 'new resource ' + d.getTime();
            var windowFeatures = 'titlebar=no,menubar=no,location=no,resizable=yes,scrollbars=yes,status=yes,directories=no,fullscreen=no,top=90,left=120,width=830,height=700';
            modal = window.open(e.target.href, windowName, windowFeatures);
            window.localStorage.setItem('modal', modal);
            // Check if the modal is closed, then refresh the list of resources.
            var checkSidebarModal = setInterval(function() {
                if (modal && modal.closed) {
                    clearInterval(checkSidebarModal);
                    // Wait to let Omeka saves the new resource, if any.
                    setTimeout(function() {
                        var s = $('#sidebar-resource-search');
                        Omeka.populateSidebarContent(s.closest('.sidebar'), s.data('search-url'), '');
                    }, 2000);
                }
            }, 100);
            return false;
        });
        // Add a new resource on modal window.
        $(document).on('click', '.modal form.resource-form #page-actions button[type=submit]', function(e) {
            // Warning: the submit may not occur when the modal is not focus.
            $('form.resource-form').submit();
            // TODO Manage error after submission (via ajax post?).
            // To avoid most issues for now, tab "Media" and "Thumbnail" are hidden.
            // Anyway, the user is working on the main resource.
            if ($('form.resource-form').data('has-error') == 1) {
                e.preventDefault();
            } else {
                window.localStorage.removeItem('modal');
                // Leave time to submit the form before closing form.
                setTimeout(function() {
                    window.close();
                }, 1000);
            }
            $('form.resource-form').removeData('has-error');
            return false;
        });
        // Cancel modal window.
        $(document).on('click', '.modal form.resource-form #page-actions a.cancel', function(e) {
            e.preventDefault();
            window.localStorage.removeItem('modal');
            window.close();
            return false;
        });

    });
})(jQuery);

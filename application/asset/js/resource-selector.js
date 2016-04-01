(function($) {
    function loadSidebarContent(sidebarContent, url, data) {
        $.ajax({
            'url': url,
            'data': data,
            'type': 'get'
        }).done(function(data) {
            sidebarContent.html(data);
            $(document).trigger('o:sidebar-content-loaded');
        }).error(function() {
            sidebarContent.html("<p>Something went wrong</p>");
        });
    }

    function searchResources() {
        var searchInput = $('#resource-list-search');
        var searchValue = searchInput.val();
        loadSidebarContent(searchInput.closest('div.sidebar-content'), $('#sidebar-resource-search .o-icon-search').data('search-url'), {'value[in][]': searchValue});
    }

    $(document).ready( function() {
        $('#select-resource').on('click', '.pagination a', function (e) {
            e.preventDefault();
            loadSidebarContent($(this).closest('div.sidebar-content'), $(this).attr('href'));
        });

        $('#select-resource').on('keydown', '.pagination input', function (e) {
            if ((e.keycode || e.which) == '13') {
                e.preventDefault();
                loadSidebarContent($(this).closest('div.sidebar-content'), $(this).data('paginationUrl'), $(this).serialize());
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
            var context = $(this);
            Omeka.closeSidebar(context);
            context.trigger('o:resource-selected');
        });

        $('#select-resource').on('click', '.select-resource', function(e) {
            e.preventDefault();
            var context = $(this);
            Omeka.closeSidebar(context);
            context.trigger('o:resource-selected');
        });
    });
})(jQuery);


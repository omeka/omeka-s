(function($) {
    $(document).ready( function() {
        $('#select-resource').on('click', '.pagination a', function (e) {
            e.preventDefault();
            var sidebarContent = $(this).parents('div.sidebar-content');
            $.ajax({
                'url': $(this).attr('href'),
                'type': 'get'
            }).done(function(data) {
                sidebarContent.html(data);
                $(document).trigger('o:sidebar-content-loaded');
            }).error(function() {
                sidebarContent.html("<p>Something went wrong</p>");
            });
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
            context.parents('.mobile').removeClass('mobile');
            context.trigger('o:resource-selected');
        });

        $('#select-resource').on('click', '.select-resource', function(e) {
            e.preventDefault();
            var context = $(this);
            Omeka.closeSidebar(context);
            context.trigger('o:resource-selected');
        });
    });

    function searchResources() {
        var searchInput = $('#resource-list-search');
        var searchValue = searchInput.val();
        var sidebarContent = searchInput.parents('div.sidebar-content');
        $.ajax({
            'url': $('#sidebar-resource-search .o-icon-search').data('search-url'),
            'data': {'value[in][]': searchValue},
            'type': 'get'
        }).done(function(data) {
            sidebarContent.html(data);
            $(document).trigger('o:sidebar-content-loaded');
        }).error(function() {
            sidebarContent.html("<p>Something went wrong</p>");
        });
    }
})(jQuery);


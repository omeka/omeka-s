(function($) {
    function searchResources() {
        var searchInput = $('#resource-list-search');
        var searchValue = searchInput.val();
        Omeka.populateSidebarContent(searchInput.closest('.sidebar'), $('#sidebar-resource-search .o-icon-search').data('search-url'), {'property[0][in][]': searchValue});
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
            Omeka.closeSidebar($(e.delegateTarget));
            $(this).trigger('o:resource-selected');
        });
    });
})(jQuery);


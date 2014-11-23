var Omeka = {
    getSidebarHandler : function(e) {
        e.preventDefault();
        var clickTarget = $(e.target);
        var sidebar = $('#content > .sidebar');
        var sidebarContent = $('#content > .sidebar > .sidebar-content');
        var sidebarDeleteContent = $('#sidebar-delete-content');
        sidebarContent.empty();
        var url = clickTarget.data('show-details-action');

        var openSidebar = function(sidebar) {
            sidebar.addClass('active');
            if (!$('#content').hasClass('sidebar-open')) {
                $('#content').addClass('sidebar-open');
            }
        };

        var close = function() {
            sidebar.removeClass('active');
            if ($('.active.sidebar').length < 1) {
                $('#content').removeClass('sidebar-open');
            }
        };

        var ajaxOpen = function() {
            if (clickTarget.hasClass('sidebar-details')) {
                sidebarDeleteContent.hide();
            }
            if (clickTarget.hasClass('sidebar-delete')) {
                sidebarDeleteContent.show();
                $('#sidebar-delete-content form').attr(
                    'action', clickTarget.data('delete-action')
                );
            }
            openSidebar(sidebar);

            $.ajax({
                'url': url,
                'type': 'get'
            }).done(function(data) {
                sidebarContent.html(data);
            });
        };

        if (clickTarget.hasClass('sidebar-close')) {
            return close;
        } else {
            return ajaxOpen;
        }
    }
};

(function($, window, document) {

    $(function() {

        // Code that depends on the DOM.
        $('.sidebar-details').click(function(e) {
            var handler = Omeka.getSidebarHandler(e);
            handler();
        });

        $('.sidebar-delete').click(function(e) {
            var handler = Omeka.getSidebarHandler(e);
            handler();
        });

        $('.sidebar-close').click(function(e) {
            var handler = Omeka.getSidebarHandler(e);
            handler();
        });

        // Switch between section tabs.
        $('a.section, .section legend').click(function(e) {
            e.preventDefault();
            var tab = $(this);
            if (!tab.hasClass('active')) {
                $('.section.active, legend.active').removeClass('active');
                if (tab.is('legend')) {
                    var section_class = tab.parents('.section').attr('id');
                } else {
                    var section_class = tab.attr('class');
                }
                var section_id = section_class.replace(/section/, '');
                tab.addClass('active');
                $('#' + section_id).addClass('active');
            }
        });
    });

    // Code that doesn't depend on the DOM.

}(window.jQuery, window, document));

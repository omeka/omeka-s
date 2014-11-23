var Omeka = {
    getSidebarHandler : function(e) {
        //first, clear everything out for reuse
        e.preventDefault();
        sidebarContent.empty();
        //set data from the clicked sidebar element action for use later in this scope
        var clickTarget = $(e.target);
        var sidebar = $('#content > .sidebar');
        var sidebarContent = $('#content > .sidebar > .sidebar-content');
        var sidebarDeleteContent = $('#sidebar-delete-content');
        var url = clickTarget.data('show-details-action');

        // internal function to open the sidebar
        var openSidebar = function(sidebar) {
            sidebar.addClass('active');
            if (!$('#content').hasClass('sidebar-open')) {
                $('#content').addClass('sidebar-open');
            }
        };

        /* Distinct functions for different actions. Reuse where you can */

        // close the sidebar
        var close = function() {
            sidebar.removeClass('active');
            if ($('.active.sidebar').length < 1) {
                $('#content').removeClass('sidebar-open');
            }
        };

        // generic function to open the sidebar and populate it with AJAXed in HTML
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

        /* Branch around which handler to return */

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

        // Sidebar handling

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

        // End Sidebar handling
        
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

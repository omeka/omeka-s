var Omeka = {
    openSidebar : function(context) {
        //if already inside top sidebar, open the inner sidebar
        if (context.parents('.sidebar').length == 0) {
            var sidebar = $('#content > .sidebar');
        } else {
            var sidebar = $('.sidebar > .sidebar');
        }
        sidebar.addClass('active');
        if (!$('#content').hasClass('sidebar-open')) {
            $('#content').addClass('sidebar-open');
        }
        var sidebarConfirm = $('#sidebar-confirm');
        if (context.hasClass('sidebar-confirm')) {
            sidebarConfirm.show();
            $('#sidebar-confirm form').attr(
                'action', context.data('sidebar-confirm-url'));
        } else {
            sidebarConfirm.hide();
        }
        this.populateSidebarContent(context, sidebar);
        return sidebar;
    },

    closeSidebar : function(context) {
        if (context.parents('.sidebar').length == 0) {
            var sidebar = $('#content > .sidebar');
        } else {
            var sidebar = $('.sidebar > .sidebar');
        }
        context.removeClass('active');
        context.parent('.active').removeClass('active');
        if ($('.active.sidebar').length < 1) {
            $('#content').removeClass('sidebar-open');
        }
    },

    populateSidebarContent : function(context, sidebar) {
        var url = context.data('sidebar-content-url');
        sidebarContent = sidebar.find('.sidebar-content');
        sidebarContent.empty();
        $.ajax({
            'url': url,
            'type': 'get'
        }).done(function(data) {
            sidebarContent.html(data);
        }).error(function() {
            sidebarContent.html("<p>Something went wrong</p>");
        });
    }

};

(function($, window, document) {

    $(function() {

        // Code that depends on the DOM.

        // Sidebar handling
        $('.sidebar-content, .sidebar-confirm').click(function(e) {
            e.preventDefault();
            var context = $(this);
            Omeka.openSidebar(context);
        });

        $('.sidebar-close').click(function(e) {
            e.preventDefault();
            var context = $(this);
            Omeka.closeSidebar(context);
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

}(window.jQuery, window, document));

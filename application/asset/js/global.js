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
        var sidebarDeleteContent = $('#sidebar-delete-content');
        sidebarDeleteContent.hide();
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
    
    setSidebarDelete : function(context, sidebar) {
        // TODO: I suspect that this HTML should be pulled in via a partial like the content?
        // TODO: Maybe break the sidebar into .sidebar-actions and .sidebar-content?
        var url = context.data('delete-url');
        sidebarContent = sidebar.find('.sidebar-content');
        sidebarContent.empty();
        var sidebarDeleteContent = $('#sidebar-delete-content');
        sidebarDeleteContent.show();
        $('#sidebar-delete-content form').attr(
            'action', url);
    },
    
    populateSidebarContent : function(context, sidebar) {
        //var context = $(this);
        var url = context.data('sidebar-content-url');
        sidebarContent = sidebar.find('.sidebar-content');
        sidebarContent.empty();
        //url = 'no';
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
        //$('.sidebar-details').click(Omeka.openSidebar);
        $('.sidebar-details').click(function() {
            var context = $(this);
            var sidebar = Omeka.openSidebar(context);
            Omeka.populateSidebarContent(context, sidebar);
            
        });
        $('.sidebar-delete').click(function(e) {
            var context = $(this);
            var sidebar = Omeka.openSidebar(context);
            Omeka.setSidebarDelete(context, sidebar);
        });

        $('.sidebar-close').click(function(e) {
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

    // Code that doesn't depend on the DOM.

}(window.jQuery, window, document));

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
            $(document).trigger('o:sidebar-content-loaded');
        }).error(function() {
            sidebarContent.html("<p>Something went wrong</p>");
        });
    },

    attachSidebarHandlers : function(context) {
        if (typeof context == 'undefined') {
            context = $('body');
        }
        context.find('a.sidebar-content, a.sidebar-confirm').click(function(e) {
            e.preventDefault();
            var context = $(this);
            Omeka.openSidebar(context);
        });

        context.find('.sidebar-close').click(function(e) {
            e.preventDefault();
            var context = $(this);
            Omeka.closeSidebar(context);
        });
    }
};

(function($, window, document) {

    $(function() {

        Omeka.attachSidebarHandlers();

        // Skip to content button. See http://www.bignerdranch.com/blog/web-accessibility-skip-navigation-links/
        $('.skip').click(function(e) {
            $('#main').attr('tabindex', -1).on('blur focusout', function() {
                $(this).removeAttr('tabindex');
            }).focus();
        });

        // Mobile navigation
        $('#mobile-nav .button').click(function(e) {
            e.preventDefault();
            var buttonClass = $(this).attr('class');
            var navId = buttonClass.replace(/button/, '');
            var navObject = $('#' + navId.replace(/o-icon-/, ''));
            if ($('header .active').length > 0) {
                if (!($(this).hasClass('active'))) {
                    $('header .active').removeClass('active');
                    $(this).addClass('active');
                    navObject.addClass('active');
                } else {
                    $('header .active').removeClass('active');
                }
            } else {
                $(this).addClass('active');
                navObject.addClass('active');
            }
        });

        // Set classes for expandable/collapsible content.
        $(document).on('click', 'a.expand, a.collapse', function(e) {
            e.preventDefault();
            $(this).toggleClass('collapse').toggleClass('expand');
            if ($('.expand-collapse-parent').length > 0) {
                $(this).parent().toggleClass('collapse').toggleClass('expand');
            }
        });

        // Show property descriptions when clicking "more-info" icon. yes
        addEditItems.on('click', '.o-icon-info', function() {
            $(this).parents('.description').toggleClass('show');
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

}(window.jQuery, window, document));

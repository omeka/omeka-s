var Omeka = {
    openSidebar : function(context) {
        //if already inside top sidebar, open the inner sidebar
        if (context.parents('.sidebar').length == 0) {
            var sidebar = $('#content > .sidebar');
        } else {
            var sidebar = $('.sidebar > .sidebar');
        }
        sidebar.addClass('active');
        if (!$('body').hasClass('sidebar-open')) {
            $('body').addClass('sidebar-open');
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
        context.removeClass('active');
        context.parent('.active').removeClass('active');
        if ($('.active.sidebar').length < 1) {
            $('body').removeClass('sidebar-open');
        }
    },

    populateSidebarContent : function(context, sidebar) {
        var url = context.data('sidebar-content-url');
        var sidebarContent = sidebar.find('.sidebar-content');
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

    filterPropertySelector : function() {
        var propertyFilter = $(this).val().toLowerCase();
        var propertySelector = $(this).closest('.property-selector');
        var totalPropertyCount = 0;
        propertySelector.find('li.vocabulary').each(function() {
            var vocabulary = $(this);
            var propertyCount = 0;
            vocabulary.find('li.property').each(function() {
                var property = $(this);
                var propertyLabel = property.data('property-label').toLowerCase();
                if (propertyLabel.indexOf(propertyFilter) > -1) {
                    // Label contains the filter string. Show the property.
                    property.show();
                    totalPropertyCount++;
                    propertyCount++;
                } else {
                    // Label doesn't contain the filter string. Hide the property.
                    property.hide();
                }
            });
            if (propertyCount > 0) {
                vocabulary.addClass('show');
            } else {
                vocabulary.removeClass('show');
            }
            vocabulary.children('span.property-count').text(propertyCount);
        });
        if (propertyFilter == '') {
            propertySelector.find('li.vocabulary').removeClass('show');
        }
        propertySelector.find('span.total-property-count').text(totalPropertyCount);
    }
};

(function($, window, document) {

    $(function() {

        // Attach sidebar triggers
        $('#content').on('click', 'a.sidebar-content, a.sidebar-confirm', function(e) {
            e.preventDefault();
            Omeka.openSidebar($(this));
        });
        
        if ($('.active.sidebar').length > 0) {
            $('#content').addClass('sidebar-open');
        }

        $('.sidebar').find('.sidebar-close').click(function(e) {
            e.preventDefault();
            Omeka.closeSidebar($(this));
        });
        
        // Skip to content button. See http://www.bignerdranch.com/blog/web-accessibility-skip-navigation-links/
        $('.skip').click(function(e) {
            $('#main').attr('tabindex', -1).on('blur focusout', function() {
                $(this).removeAttr('tabindex');
            }).focus();
        });

        // If Javascript being used, provide wrapper for collapsible field descriptions.
        $('.field-meta .expand, .field-meta .collapse').each(function(e) {
            var nextSiblings = $(this).nextAll();
            nextSiblings.wrapAll('<div class="collapsible"></div>');
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
            var toggle = $(this);
            toggle.toggleClass('collapse').toggleClass('expand').toggleClass('o-icon-right').toggleClass('o-icon-down');
            if ($('.expand-collapse-parent').length > 0) {
                toggle.parent().toggleClass('collapse').toggleClass('expand');
            }
            if (toggle.hasClass('expand')) {
                toggle.attr('aria-label','Expand');
            } else {
                toggle.attr('aria-label','Collapse');
            }
        });

        // Switch between section tabs.
        $('.section-nav a[href^="#"').click(function (e) {
            e.preventDefault();
            var tab = $(this).parent();
            if (!tab.hasClass('active')) {
                $('.section.active, .section-nav li.active').removeClass('active');
                tab.addClass('active');
                $($(this).attr('href')).addClass('active');
            }
        });
        
        // Property selector toggle children
        $('.property-selector li.vocabulary').on('click', function(e) {
            e.stopPropagation();
            if ($(this).children('li')) {
                $(this).toggleClass('show');
            }
        });
        
        $('.property-selector-filter').on('keydown', function(e) {
            if (e.keyCode == 13) {
                e.stopPropagation();
                e.preventDefault();
            }
        });

        // Property selector, filter properties.
        $('.property-selector-filter').on('keyup', (function() {
            var timer = 0;
            return function() {
                clearTimeout(timer);
                timer = setTimeout(Omeka.filterPropertySelector.bind(this), 400);
            }
        })())

        // Autoposition tooltip.
        $('body').on('click', '.o-icon-info', function(e) {
            e.preventDefault();
            var moreInfoIcon = $(this);
            var fieldDesc = moreInfoIcon.next('.field-comment');
            fieldDesc.toggleClass('open');
            var fieldDescBottom = moreInfoIcon.offset().top + moreInfoIcon.outerHeight() + fieldDesc.outerHeight() - $(window).scrollTop();
            fieldDesc.toggleClass('above', fieldDescBottom > $(window).height());
        });
    });

}(window.jQuery, window, document));

var Omeka = {
    openSidebar : function(context,target) {
        //close delete sidebar if open
        if (!context.hasClass('delete')) {
            if ($('#delete').hasClass('active')) {
                $('#delete').removeClass('active');
            }
        }

        //if already inside top sidebar, open the inner sidebar
        if (context.parents('.sidebar').length == 0) {
            var sidebar = $('#content > .sidebar');
        } else {
            var sidebar = $('.sidebar > .sidebar');
        }
        if (typeof target !== 'undefined') {
            var sidebar = $(target + '.sidebar');
        }
        if (!$('body').hasClass('sidebar-open') && !$('body').hasClass('section-sidebar-open')) {
            $('body').addClass('sidebar-open');
        }

        if (context.attr('data-sidebar-content-url')) {
            this.populateSidebarContent(context, sidebar);
        }
        sidebar.addClass('active');
        return sidebar;
    },

    closeSidebar : function(context) {
        context.removeClass('active');
        context.closest('.active').removeClass('active');
        if ($('.active.sidebar, .always-open.sidebar').length == 0) {
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

    switchActiveSection: function (section) {
        $('.section.active, .section-nav li.active').removeClass('active');
        section.addClass('active');
        $('.section-nav a[href="#' + section.attr('id') + '"]').parent().addClass('active');
        if (section.find('.always-open.sidebar, .active.sidebar').length > 0) {
            $('body').addClass('section-sidebar-open');
        } else {
            $('body').removeClass('section-sidebar-open');
        }
    },

    filterSelector : function() {
        var filter = $(this).val().toLowerCase();
        var selector = $(this).closest('.selector');
        var totalCount = 0;
        selector.find('li.selector-parent').each(function() {
            var parent = $(this);
            var count = 0;
            parent.find('li.selector-child').each(function() {
                var child = $(this);
                var label = child.data('child-search').toLowerCase();
                if (label.indexOf(filter) > -1) {
                    // Label contains the filter string. Show the child.
                    child.show();
                    totalCount++;
                    count++;
                } else {
                    // Label doesn't contain the filter string. Hide the child.
                    child.hide();
                }
            });
            if (count > 0) {
                parent.addClass('show');
            } else {
                parent.removeClass('show');
            }
            parent.children('span.selector-child-count').text(count);
        });
        if (filter == '') {
            selector.find('li.selector-parent').removeClass('show');
        }
        selector.find('span.selector-total-count').text(totalCount);
    },

    updateSearch: function () {
        var checkedOption = $("#advanced-options input[type='radio']:checked ");
        var checkedLabel = checkedOption.next().text().toLowerCase();
        var actionURL = checkedOption.data('action');
        $("#search-form").attr("action", actionURL);
        $("#search-form > input[type='text']").attr("placeholder", "Search " + checkedLabel);
    },

    scrollTo: function(wrapper) {
        if (wrapper.length) {
            $('html, body').animate({
                scrollTop: (wrapper.offset().top -100)
            },200);
        }
    },

    markDirty: function(form) {
        $(form).data('omekaFormDirty', true);
    }
};

(function($, window, document) {

    $(function() {

        $('#content').on('click', 'a.sidebar-content', function(e) {
            e.preventDefault();
            var sidebarSelector = $(this).data('sidebar-selector');
            Omeka.openSidebar($(this), sidebarSelector);
        });

        $('#content').on('click', '.button.delete, button.delete', function(e) {
            e.preventDefault();
            Omeka.openSidebar($(this), '#delete');
        });

        if ($('.always-open.sidebar').length > 0) {
            $('#content').addClass('sidebar-open');
        }

        $('.sidebar').find('.sidebar-close').click(function(e) {
            e.preventDefault();
            Omeka.closeSidebar($(this));
        });

        // Open sidebars on mobile
        $('button.mobile-only').on('click', function(e) {
            e.preventDefault();
            var mobileButton = $(this);
            var sidebarId = mobileButton.attr('id');
            sidebarId = '#' + sidebarId.replace('-button', '');
            $(sidebarId).addClass('active');
            mobileButton.parents('form').bind('DOMSubtreeModified', function() {
                $('.sidebar.always-open').removeClass('active');
                $(this).unbind('DOMSubtreeModified');
            });
        });

        // Make resource public or private
        $('#content').on('click', 'a.o-icon-private, a.o-icon-public', function(e) {
            e.preventDefault();
            var isPublicIcon = $(this);
            $(this).toggleClass('o-icon-private').toggleClass('o-icon-public');
            var isPublicHiddenValue = $(this).next('[type="hidden"]');
            if (isPublicHiddenValue.val() == 0) {
                isPublicIcon.attr('aria-label', 'Make private');
                isPublicIcon.attr('title', 'Make private');
                isPublicHiddenValue.attr('value', 1);
            } else {
                isPublicIcon.attr('aria-label', 'Make public');
                isPublicIcon.attr('title', 'Make public');
                isPublicHiddenValue.attr('value', 0);
            }
        });

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
            var toggle = $(this);
            toggle.toggleClass('collapse').toggleClass('expand');
            if (toggle.hasClass('expand')) {
                toggle.attr('aria-label','Expand');
            } else {
                toggle.attr('aria-label','Collapse');
            }
        });

        // Switch between section tabs.
        $('.section-nav a[href^="#"]').click(function (e) {
            e.preventDefault();
            Omeka.switchActiveSection($($(this).attr('href')));
        });

        $('.section > legend').click(function() {
            $(this).parent().toggleClass('mobile-active');
        });

        // Automatically switch to sections containing invalid elements on submit
        // (Use a capturing event listener because 'invalid' doesn't bubble)
        document.body.addEventListener('invalid', function (e) {
            var target, section;
            target = $(e.target);
            if (!target.is(':input')) {
                return;
            }
            section = target.parents('.section');
            if (section.length && !section.hasClass('active')) {
                Omeka.switchActiveSection(section);
            }
        }, true);

        // Property selector toggle children
        $('.selector li.selector-parent').on('click', function(e) {
            e.stopPropagation();
            if ($(this).children('li')) {
                $(this).toggleClass('show');
            }
        });

        $('.selector-filter').on('keydown', function(e) {
            if (e.keyCode == 13) {
                e.stopPropagation();
                e.preventDefault();
            }
        });

        // Property selector, filter properties.
        $('.selector-filter').on('keyup', (function() {
            var timer = 0;
            return function() {
                clearTimeout(timer);
                timer = setTimeout(Omeka.filterSelector.bind(this), 400);
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

        $('#search-form').change(Omeka.updateSearch);
        Omeka.updateSearch();
    });

    $(window).load(function() {
        var setSubmittedFlag = function () {
            $(this).data('omekaFormSubmitted', true);
        };

        $('form[method=POST]').each(function () {
            var form = $(this);
            form.data('omekaFormOriginalData', form.serialize());
            form.submit(setSubmittedFlag);
        });

        $(window).on('beforeunload', function() {
            var preventNav = false;
            $('form[method=POST]').each(function () {
                var form = $(this);
                var originalData = form.data('omekaFormOriginalData');
                if (form.data('omekaFormSubmitted')) {
                    return;
                }

                form.trigger('o:before-form-unload');

                if (form.data('omekaFormDirty')
                    || (originalData && originalData !== form.serialize())
                ) {
                    preventNav = true;
                    return false;
                }
            });

            if (preventNav) {
                return 'You have unsaved changes.';
            }
        });
    });

}(window.jQuery, window, document));

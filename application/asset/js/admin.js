var Omeka = {
    openSidebar : function(sidebar) {
        sidebar.addClass('active');
        this.reserveSidebarSpace();
        sidebar.trigger('o:sidebar-opened');
    },

    closeSidebar : function(sidebar) {
        sidebar.removeClass('active');
        this.reserveSidebarSpace();
        sidebar.trigger('o:sidebar-closed');
    },

    reserveSidebarSpace: function() {
        var openSidebars = $('.active.sidebar, .always-open.sidebar').length
            - $('.section:not(.active) .active.sidebar, .section:not(.active) .always-open.sidebar').length;
        $('body').toggleClass('sidebar-open', openSidebars > 0);
    },

    populateSidebarContent : function(sidebar, url, data) {
        var sidebarContent = sidebar.find('.sidebar-content');
        sidebar.addClass('loading');
        sidebarContent.empty();
        $.get(url, data)
            .done(function(data) {
                sidebarContent.html(data);
                $(sidebar).trigger('o:sidebar-content-loaded');
            })
            .fail(function() {
                sidebarContent.html('<p>' + Omeka.jsTranslate('Something went wrong') + '</p>');
            })
            .always(function () {
                sidebar.removeClass('loading');
            });
    },

    switchActiveSection: function (section) {
        var closedSection = $('.section.active');
        var sectionId = '#' + section.attr('id');
        $('.section.active, .section-nav li.active').removeClass('active');
        section.addClass('active');
        $('.section-nav a[href="' + sectionId + '"]').parent().addClass('active');
        if (!$('body').hasClass('no-section-hashes')) {
            history.replaceState(null, document.title, sectionId);
        }
        this.reserveSidebarSpace();
        if (!closedSection.is(section)) {
            if (closedSection.length > 0) {
                closedSection.trigger('o:section-closed');
            }
            section.trigger('o:section-opened');
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
                parent.show();
            } else {
                parent.removeClass('show');
                parent.hide();
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
        $("#search-form").attr("action", checkedOption.data('action'));
        $("#search-form > input[type='text']").attr("placeholder", checkedOption.data('inputPlaceholder')).attr("aria-label", checkedOption.data('inputPlaceholder'));
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
    },

    fixIframeAspect: function () {
        $('iframe').each(function () {
            var aspect = $(this).attr('height') / $(this).attr('width');
            $(this).height($(this).width() * aspect);
        });
    },

    framerateCallback: function(callback) {
        var waiting = false;
        callback = callback.bind(this);
        return function () {
            if (!waiting) {
                waiting = true;
                window.requestAnimationFrame(function () {
                    callback();
                    waiting = false;
                });
            }
        }
    },

    warnIfUnsaved: function() {
        var setSubmittedFlag = function () {
            $(this).data('omekaFormSubmitted', true);
        };

        var setOriginalData = function () {
            $(this).data('omekaFormOriginalData', $(this).serialize());
        };

        var formsToCheck = $('form[method=POST]:not(.disable-unsaved-warning)');
        formsToCheck.on('o:form-loaded', setOriginalData);
        formsToCheck.each(function () {
            var form = $(this);
            form.trigger('o:form-loaded');
            form.submit(setSubmittedFlag);
        });

        $(window).on('beforeunload', function() {
            var preventNav = false;
            formsToCheck.each(function () {
                var form = $(this);
                var originalData = form.data('omekaFormOriginalData');
                var hasFile = false;
                if (form.data('omekaFormSubmitted')) {
                    return;
                }

                form.trigger('o:before-form-unload');

                form.find('input[type=file]').each(function () {
                    if (this.files.length) {
                        hasFile = true;
                        return false;
                    }
                });

                if (form.data('omekaFormDirty')
                    || (originalData && originalData !== form.serialize())
                    || hasFile
                ) {
                    preventNav = true;
                    return false;
                }
            });

            if (preventNav) {
                return 'You have unsaved changes.';
            }
        });
    },

    manageSelectedActions: function() {
        var selectedOptions = $('[value="update-selected"], [value="delete-selected"]');
        if ($('.batch-edit td input[type="checkbox"]:checked').length > 0) {
            selectedOptions.removeAttr('disabled');
        } else {
            selectedOptions.attr('disabled', true);
            $('.batch-actions-select').val('default');
            $('.batch-actions .active').removeClass('active');
            $('.batch-actions .default').addClass('active');
        }
    },

    // @see http://stackoverflow.com/questions/7035825/regular-expression-for-a-language-tag-as-defined-by-bcp47
    // Removes `|[A-Za-z]{4}|[A-Za-z]{5,8}` from the "language" portion becuase,
    // while in the spec, it does not represent current usage.
    langIsValid: function(lang) {
        return lang.match(/^(((en-GB-oed|i-ami|i-bnn|i-default|i-enochian|i-hak|i-klingon|i-lux|i-mingo|i-navajo|i-pwn|i-tao|i-tay|i-tsu|sgn-BE-FR|sgn-BE-NL|sgn-CH-DE)|(art-lojban|cel-gaulish|no-bok|no-nyn|zh-guoyu|zh-hakka|zh-min|zh-min-nan|zh-xiang))|((([A-Za-z]{2,3}(-([A-Za-z]{3}(-[A-Za-z]{3}){0,2}))?))(-([A-Za-z]{4}))?(-([A-Za-z]{2}|[0-9]{3}))?(-([A-Za-z0-9]{5,8}|[0-9][A-Za-z0-9]{3}))*(-([0-9A-WY-Za-wy-z](-[A-Za-z0-9]{2,8})+))*(-(x(-[A-Za-z0-9]{1,8})+))?)|(x(-[A-Za-z0-9]{1,8})+))$/);
    }
};

(function($, window, document) {

    $(function() {
        Omeka.reserveSidebarSpace();

        if (window.location.hash && $('.section').filter(window.location.hash).length) {
            // Open the section that corresponds to the URL fragment identifier.
            Omeka.switchActiveSection($(window.location.hash));
        }

        $('#content').on('click', 'a.sidebar-content', function(e) {
            e.preventDefault();
            var sidebarSelector = $(this).data('sidebar-selector') || '#content > .sidebar';
            var sidebar = $(sidebarSelector);

            if ($(this).data('sidebar-content-url')) {
                Omeka.populateSidebarContent(sidebar, $(this).data('sidebar-content-url'));
            }
            Omeka.openSidebar(sidebar);
        });

        $('#content').on('click', '.button.delete, button.delete', function(e) {
            e.preventDefault();
            var sidebar = $('#delete');
            Omeka.openSidebar(sidebar);

            // Auto-close if other sidebar opened
            $('body').one('o:sidebar-opened', '.sidebar', function () {
                if (!sidebar.is(this)) {
                    Omeka.closeSidebar(sidebar);
                }
            });
        });

        $('#content').on('click', '.sidebar-close', function(e) {
            e.preventDefault();
            Omeka.closeSidebar($(this).closest('.sidebar'));
        });

        $('#content').on('click', '.sidebar .pagination a', function (e) {
            e.preventDefault();
            Omeka.populateSidebarContent($(this).closest('.sidebar'), $(this).attr('href'));
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
                isPublicIcon.attr('aria-label', Omeka.jsTranslate('Make private'));
                isPublicIcon.attr('title', Omeka.jsTranslate('Make private'));
                isPublicHiddenValue.attr('value', 1);
            } else {
                isPublicIcon.attr('aria-label', Omeka.jsTranslate('Make public'));
                isPublicIcon.attr('title', Omeka.jsTranslate('Make public'));
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
                toggle.attr('aria-label', Omeka.jsTranslate('Expand')).attr('title', Omeka.jsTranslate('Expand'));
                toggle.trigger('o:collapsed');
            } else {
                toggle.attr('aria-label', Omeka.jsTranslate('Collapse')).attr('title', Omeka.jsTranslate('Collapse'));
                toggle.trigger('o:expanded');
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

        // Maintain iframe aspect ratios
        $(window).on('load resize', Omeka.framerateCallback(Omeka.fixIframeAspect));
        Omeka.fixIframeAspect();

        $(function() {
            // Wait until we're done manipulating things to enable CSS transitions
            $('body').addClass('transitions-enabled');

            Omeka.warnIfUnsaved();
        });

        $('body').on('blur', 'input,textarea,select', function() {
            $(this).addClass('touched');
        });

        $(document).trigger('enhance.tablesaw');

        $('.select-all').change(function() {
            if (this.checked) {
                $('.batch-edit td input[type=checkbox]:not(:disabled)').prop('checked', true);
            } else {
                $('.batch-edit td input[type=checkbox]:checked').prop('checked', false);
            }
            Omeka.manageSelectedActions();
        });

        $('.batch-edit td input[type="checkbox"]').change(function() {
            if ($('.select-all:checked').length > 0) {
                $('.select-all').prop('checked', false); 
            }
            Omeka.manageSelectedActions();
        });

        $('.batch-actions-select').change(function() {
            var selectedAction = $("option:selected", this);
            var selectedActionClass = "." + selectedAction.val();
            $('.batch-actions .active').removeClass('active');
            $(selectedActionClass).addClass('active');
        });

        $('.chosen-select').chosen(chosenOptions);

        // Along with CSS, this fixes a known bug where a Chosen dropdown at the
        // bottom of the page breaks layout.
        // @see https://github.com/harvesthq/chosen/issues/155#issuecomment-173238083
        $(document).on('chosen:showing_dropdown', '.chosen-select', function(e) {
            var chosenContainer = $(e.target).next('.chosen-container');
            var dropdown = chosenContainer.find('.chosen-drop');
            var dropdownTop = dropdown.offset().top - $(window).scrollTop();
            var dropdownHeight = dropdown.height();
            var viewportHeight = $(window).height();
            if (dropdownTop + dropdownHeight > viewportHeight) {
                chosenContainer.addClass('chosen-drop-up');
            }
        });
        $(document).on('chosen:hiding_dropdown', '.chosen-select', function(e) {
            $(e.target).next('.chosen-container').removeClass('chosen-drop-up');
        });
    });

}(window.jQuery, window, document));

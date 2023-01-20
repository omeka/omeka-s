(function($, window, document) {

    $(function() {
        Omeka.reserveSidebarSpace();

        // Open the section that corresponds to the URL fragment identifier.
        if (window.location.hash && !$('body').hasClass('no-section-hashes')) {
            var possibleSection = document.getElementById(window.location.hash.slice(1));
            if (possibleSection && possibleSection.classList.contains('section')) {
                Omeka.switchActiveSection($(possibleSection));
            }
        }

        $('#content').on('click', '.button.cancel', function(e) {
            e.preventDefault();
            window.history.go(-1);
        });

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
        })());

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

        // Close page action menu if it is open and the user clicks outside it.
        $(document).click(function(e) {
            if (null === e.target.closest('.page-action-menu')) {
                Omeka.closeOpenPageActionsMenu(e);
            }
        });

        $('#page-actions').on('click', '.page-action-menu .expand', function(e) {
            Omeka.closeOpenPageActionsMenu(e);
        });

        $(document).on('keyup, change', 'input.validate-language', function(e) {
            if ('' === this.value || Omeka.langIsValid(this.value)) {
                this.setCustomValidity('');
            } else {
                this.setCustomValidity(Omeka.jsTranslate('Please enter a valid language tag'))
            }
        });

    });

}(window.jQuery, window, document));

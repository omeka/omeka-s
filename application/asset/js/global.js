var Omeka = {
    openSidebar : function(sidebar) {
        sidebar.addClass('active');
        this.reserveSidebarSpace();
        sidebar.trigger('o:sidebar-opened');
        if ($('.active.sidebar').length > 1) {
            var highestIndex = 3; // The CSS currently defines the default sidebar z-index as 3.
            $('.active.sidebar').each(function() {
                var currentIndex = parseInt($(this).css('zIndex'), 10);
                if (currentIndex > highestIndex) {
                    highestIndex = currentIndex;
                }
            });
            sidebar.css('zIndex', highestIndex + 1);
        }
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
                if ((label.indexOf(filter) < 0) || (child.hasClass('added'))) {
                    // Label doesn't contain the filter string. Hide the child.
                    child.addClass('filter-hidden');
                } else {
                    // Label contains the filter string. Show the child.
                    child.removeClass('filter-hidden');
                    totalCount++;
                    count++;
                }
            });
            if (count > 0) {
                parent.removeClass('empty');
            } else {
                parent.addClass('empty');
            }
            parent.children('span.selector-child-count').text(count);
        });
        if (filter == '') {
            selector.find('li.selector-parent').removeClass('show');
            $('.filter-match').removeClass('filter-match');
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

    initializeSelector : function(tableId, selectorId) {
        var table = $(tableId);
        var existingRowData = table.data('existing-rows');
        var rowTemplate = $($.parseHTML(table.data('rowTemplate')));
        var selector = $(selectorId);
        var totalCount = selector.find('.resources-available').data('all-resources-count');
        var selectorCount = selector.find('.selector-total-count');
      
        var parentToggle = function(e) {
            e.stopPropagation();
            if ($(this).children('li')) {
                $(this).toggleClass('show');
            }
        }
        
        var appendRow = function(id) {
            if (table.find(".resource-id[value='" + id + "']").length) {
                return;
            }
            var tableRow = rowTemplate.clone();
            var selectorRow = selector.find('[data-resource-id="' + id + '"]');
            tableRow.find('.resource-id').val(id);
            tableRow.find('.data-value').each(function() {
                var tableRowCell = $(this);
                var tableRowKey = tableRowCell.data('row-key');
                var tableRowValue = selectorRow.data(tableRowKey);
                tableRowCell.text(tableRowValue);
            });
            selectorRow.addClass('added');
            table.append(tableRow).removeClass('empty').trigger('appendRow');
            updateResourceCount(id);
        }
    
        var updateResourceCount = function(id) {
            var resource = selector.find('[data-resource-id="' + id + '"]');
            var resourceParent = resource.parents('.selector-parent');
            var childCount = resourceParent.find('.selector-child-count').first();
            if (resource.hasClass('added')) {
                var newTotalCount = parseInt(selectorCount.text()) - 1;
                var newChildCount = parseInt(childCount.text()) - 1;
            } else {
                var newTotalCount = parseInt(selectorCount.text()) + 1;
                var newChildCount = parseInt(childCount.text()) + 1;
            }
            selectorCount.text(newTotalCount);
            childCount.text(newChildCount);
            var currentRows = table.find('.resource-row').length;
            if (totalCount - currentRows == 0) {
                selector.find('.resources-available').addClass('empty');
            } else {
                selector.find('.resources-available').removeClass('empty');
            }
            if (newChildCount == 0) {
                resourceParent.addClass('empty');
            } else {
                resourceParent.removeClass('empty');
            }
        }
    
        if (existingRowData.length > 0) {
            $.each(existingRowData, function() {
                appendRow(this.id);
            });
            table.removeClass('empty');
        }
    
        // Add the selected resource to the edit panel.
        $(selectorId + ' .selector-child').on('click', function(e) {
            e.stopPropagation();
            var selectorRow = $(this);
            var selectorParent = selectorRow.parents('.selector-parent');
            selectorParent.unbind('click');
            appendRow(selectorRow.data('resource-id'));
            selectorParent.bind('click', parentToggle);
            Omeka.scrollTo(table.find('.resource-row:last-child'));
        });

        // Remove a resource from the edit panel.
        table.on('click', '.o-icon-delete', function(e) {
            e.preventDefault();
            var row = $(this).closest('.resource-row');
            var resourceId = row.find('.resource-id').val();
            selector.find('[data-resource-id="' + resourceId + '"]').removeClass('added');
            row.remove();
            updateResourceCount(resourceId);
            if ($('.resource-row').length < 1) {
                table.addClass('empty');
            }
        });
    },


    // @see http://stackoverflow.com/questions/7035825/regular-expression-for-a-language-tag-as-defined-by-bcp47
    // Removes `|[A-Za-z]{4}|[A-Za-z]{5,8}` from the "language" portion because,
    // while in the spec, it does not represent current usage.
    langIsValid: function(lang) {
        return lang.match(/^(((en-GB-oed|i-ami|i-bnn|i-default|i-enochian|i-hak|i-klingon|i-lux|i-mingo|i-navajo|i-pwn|i-tao|i-tay|i-tsu|sgn-BE-FR|sgn-BE-NL|sgn-CH-DE)|(art-lojban|cel-gaulish|no-bok|no-nyn|zh-guoyu|zh-hakka|zh-min|zh-min-nan|zh-xiang))|((([A-Za-z]{2,3}(-([A-Za-z]{3}(-[A-Za-z]{3}){0,2}))?))(-([A-Za-z]{4}))?(-([A-Za-z]{2}|[0-9]{3}))?(-([A-Za-z0-9]{5,8}|[0-9][A-Za-z0-9]{3}))*(-([0-9A-WY-Za-wy-z](-[A-Za-z0-9]{2,8})+))*(-(x(-[A-Za-z0-9]{1,8})+))?)|(x(-[A-Za-z0-9]{1,8})+))$/);
    },

    // Index of property search values.
    propertySearchIndex: null,

    // Prepare the search form. Must be called any time the form is loaded.
    prepareSearchForm: function(form) {
        // The property values need an index.
        Omeka.propertySearchIndex = $('#property-queries .value').length;
        // Prepare the multi-value templates used for duplicating values.
        $('.multi-value.field').each(function() {
            var field = $(this);
            var value = field.find('.value').first().clone();
            var valueHtml = value.wrap('<div></div>').parent().html();
            field.data('field-template', valueHtml);
        });
        form.find('.query-type').each(Omeka.disableQueryTextInput);
    },

    // Disable query text according to query type.
    disableQueryTextInput: function() {
        var queryType = $(this);
        var queryText = queryType.siblings('.query-text');
        if (queryType.val() === 'ex' || queryType.val() === 'nex') {
            queryText.prop('disabled', true);
        } else {
            queryText.prop('disabled', false);
        }
    },

    // Clean the search query of empty or otherwise unneeded inputs.
    cleanSearchQuery: function(form) {
        form.find(':input').each(function(index) {
            const input = $(this);
            const inputName = input.attr('name');
            const inputValue = input.val();
            if (inputName && '' === inputValue) {
                const inputNames = [
                    'fulltext_search',
                    'resource_class_id[]',
                    'resource_template_id[]',
                    'item_set_id[]',
                    'site_id',
                    'owner_id',
                    'media_type',
                    'sort_by',
                    'sort_order',
                ];
                if (inputNames.includes(inputName)) {
                    input.prop('name', '');
                } else {
                    const match = inputName.match(/property\[(\d+)\]\[text\]/);
                    if (match) {
                        const propertyType = form.find(`[name="property[${match[1]}][type]"]`);
                        if (['eq', 'neq', 'in', 'nin', 'res', 'nres'].includes(propertyType.val())) {
                            form.find(`[name="property[${match[1]}][joiner]"]`).prop('name', '');
                            form.find(`[name="property[${match[1]}][property]"]`).prop('name', '');
                            form.find(`[name="property[${match[1]}][text]"]`).prop('name', '');
                            propertyType.prop('name', '');
                        }
                    }
                }
            }
        });
    },

    closeOpenPageActionsMenu: function(e) {
        var button = $('.page-action-menu .collapse');
        if (button.length > 0) {
            button.click();
        }
    }
};

$(document).ready(function() {
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
});

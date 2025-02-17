
/**
 * Initially based on Omeka S omeka2importer.js and resource-core.js.
 */
(function ($) {

    $(document).ready(function() {
        /*
         * Init.
         */

        var activeElement = null;

        var defaultSidebarHtml = null;

        var actionsHtml = '<ul class="actions">'
            + '<li><a aria-label="' + Omeka.jsTranslate('Remove mapping') + '" title="' + Omeka.jsTranslate('Remove mapping') + '" class="o-icon-delete remove-mapping" href="#" style="display: inline;"></a></li>'
            + '</ul>';

        /*
         * Active first tab section 
         */

        activeFirstTab($('.section-nav'));

        function activeFirstTab(sectionNav) {
            var firstTab = $(sectionNav).find('ul li:first-child');
            firstTab.addClass('active');
            Omeka.switchActiveSection($($(sectionNav).find('a[href^="#"]').attr('href')));
        }

        $(document).on('change', '.level_mapping', (function() {
            var levelMapping = $(this).val();
            var activTabLink = $('.section-nav').find('ul li.active a');
            appendMappingIcon(activTabLink, levelMapping);
        }));

        $(document).on('click', '.skip_option', (function() {
            var toSkip = $(this).prop("checked");
            var activTabLink = $('.section-nav').find('ul li.active a');
            var nearestTable = $(this).closest("table");
            var level = ($(nearestTable).data('level'));
            var levelMapping = $(this).closest('tr').find('.level_mapping');
            
            $(('.table-' + level)).toggle('show');
            activTabLink.toggleClass('skipping_mapping');
            if (toSkip) {
                levelMapping.val('skipped');
                levelMapping.prop('disabled', true);
                activTabLink.removeClass();
                activTabLink.addClass('skipping_mapping');
            } else {
                levelMapping.val('');
                levelMapping.prop('disabled', false);
            }
        }));

        function appendMappingIcon(element, mapping) {
            $(element).removeClass();
            $(element).removeAttr('style');
            var iconsLevelMap = {
                item_sets: 'o-icon-item-sets',
                items: 'o-icon-items',
            };
            var icon = iconsLevelMap[mapping];
            if (icon) {
                var label = $(element).text();
                $(element).text(' ' + label);
                $(element).addClass(icon);
            } else {
                if (mapping === "none") {
                    $(element).css('font-style', 'italic');
                }
            }
        }

        /*
         * Rebinding chosen selects and property selector after sidebar hydration.
         */

         function rebindInputs(sidebar) {
              // Remove old chosen html and rebind event.
              sidebar.find('.chosen-container').remove();
              sidebar.find('.chosen-select').chosen(chosenOptions);

              // Rebind property selector.
              sidebar.find('li.selector-parent').on('click', function(e) {
                e.stopPropagation();
                  if ($(this).children('li')) {
                      $(this).toggleClass('show');
                  }
              });

              sidebar.find('.selector-filter').on('keydown', function(e) {
                  if (e.keyCode == 13) {
                      e.stopPropagation();
                      e.preventDefault();
                  }
              });

              // Property selector, filter properties.
              sidebar.find('.selector-filter').on('keyup', (function() {
                  var timer = 0;
                  return function() {
                      clearTimeout(timer);
                      timer = setTimeout(Omeka.filterSelector.bind(this), 400);
                  }
              })())

              // Specific sidebar actions for property selector.
              sidebar.find('#property-selector li.selector-child').on('click', function(e){
                e.stopPropagation();
                    $(this).toggleClass('selected');
              });
         }

        /*
         * Sidebar chooser (buttons on each mappable element).
         */

        $('.column-header + .actions a').on('click', function(e) {
            e.preventDefault();

            if (activeElement !== null) {
                activeElement.removeClass('active');
            }
            
            activeElement = $(e.target).closest('tr.mappable');
            activeElement.addClass('active');

            var actionElement = $(this);
            $('.sidebar-chooser li').removeClass('active');
            actionElement.parent().addClass('active');
            var target = actionElement.data('sidebar-selector');

            var sidebar = $(target);
            if (!sidebar.hasClass('active') ) {
                defaultSidebarHtml = sidebar.html();
            }
            var columnName = activeElement.data('column');
            if (sidebar.find('.column-name').length > 0) {
                $('.column-name').text(columnName);
            } else {
                sidebar.find('h3').append(' <span class="column-name">' + columnName + '</span>');
            }

            var currentSidebar = $('.sidebar.active');
            if (currentSidebar.attr('id') != target) {
                currentSidebar.removeClass('active');
                sidebar.html(defaultSidebarHtml);
                rebindInputs(sidebar);
            }

            Omeka.openSidebar(sidebar);
            populateSidebar();
        });

        function populateSidebar() {
            $('.active.element .options :input:not(:disabled)').each(function() {
                var optionInput = $(this);
                var optionName = optionInput.parents('.option').attr('class');
                optionName = optionName.replace(' option', '').replace('column-', '');
                var sidebarOptionInput = $('#column-options .' + optionName + ' :input');
                
                if (sidebarOptionInput.attr('type') == "text") {
                    sidebarOptionInput.val(optionInput.val());
                }
               
            });
        }

        /*
         * Sidebar actions (data mapping and options on the active element).
         */

        $('#resource-type-column').change(function() {
            $('.mapping.resource-type').remove();
            var resourceTypeSelect = $(this);
            var flagName = resourceTypeSelect.data('flag-name');
            var flagValue = 1;
            var flagLabel = resourceTypeSelect.data('flag-label');
            var flagLiClass = resourceTypeSelect.data('flag-class');
            var selectedColumnName = resourceTypeSelect.val();

            if (selectedColumnName == "") {
                return;
            }
            activeElement = $('[name="' + selectedColumnName + '"]').parents('.mappable.element');
            applyMappings(flagName, flagValue, flagLiClass, flagLabel);
            activeElement.find('.resource-type .actions').remove();
            activeElement = null;
        });

        $(document).on('click', '.sidebar-close, .confirm-panel button', function() {
            resetActiveColumns();
        });

        // Generic sidebar actions.
        $(document).on('o:expanded', '#add-ead-mapping a', function() {
            var mappingGroup = $(this).parents('.mapping-group');
            var mappingGroupID = mappingGroup.attr('id');
            $('#add-ead-mapping .mapping-group:not(#' + mappingGroupID + ') a.collapse').each(function() {
                var openMappingGroup = $(this);
                openMappingGroup.removeClass('collapse').addClass('expand');
                openMappingGroup.attr('aria-label', Omeka.jsTranslate('Expand')).attr('title', Omeka.jsTranslate('Expand'));
                openMappingGroup.trigger('o:collapsed');
            });
        });

        $(document).on('click', '.flags .confirm-panel button', function() {
            var sidebar = $(this).parents('.sidebar');
            sidebar.find('[data-flag-class]').each(function() {
                var flagInput = $(this);
                var flagLiClass = flagInput.data('flag-class');

                if (flagInput.is('select')) {
                    var flagLabel = flagInput.data('flag-label');
                    if (flagInput.hasClass('chosen-select')) {
                        if (flagInput.next('.chosen-container').parents('.toggle-view:hidden').length > 0) {
                            return;
                        }
                        var flagValue = flagInput.chosen().val();
                        if (flagValue == '') {
                            return;
                        }
                        var flagName = flagInput.chosen().data('flag-name');

                        // Show flag name instead of selected text for mapping using property selector.
                        if (flagInput.parents('.mapping').hasClass('property')) {
                            var flagLabel = flagLabel + ' [' + flagValue + ']';
                        } else {
                            var flagLabel = flagLabel + ' [' + flagInput.chosen().text() + ']';
                        }
                    }
                    else {
                        if (!flagInput.hasClass('touched')) {
                            return;
                        }
                        var flagSelected = flagInput.find(':selected');
                        var flagValue = flagSelected.val();
                        var flagName = flagSelected.data('flag-name');
                        var flagLabel = flagLabel + ' [' + flagSelected.text() + ']';
                    }

                    applyMappings(flagName, flagValue, flagLiClass, flagLabel);
                }
            });

            sidebar.find('.selector-child.selected').each(function () {
                // Looks like a stopPropagation on the selector-parent forces me to
                // bind the event lower down the DOM, then work back up to the li.
                var targetLi = $(this);

                // First, check if the property is already added.
                var hasMapping = activeElement.find('ul.mappings li[data-property-id="' + targetLi.data('property-id') + '"]');
                if (hasMapping.length === 0) {
                    var elementId = activeElement.data('element-id');
                    var column = activeElement.data('column');
                    activeElement.find('.make_inherit').prop('disabled', false);
                    var newInput = $('<input type="hidden" name="nodes_mapping[' + column + '][' + elementId + '][properties][]" ></input>');
                    newInput.val(targetLi.data('property-id'));
                    var newMappingLi = $('<li class="mapping property" data-property-id="' + targetLi.data('property-id') + '">' + targetLi.data('child-search') + actionsHtml  + '</li>');
                    newMappingLi.append(newInput);
                    // For ergonomy, group elements by type.
                    var existingMappingLi = activeElement.find('ul.mappings .property').filter(':last');
                    if (existingMappingLi.length) {
                        existingMappingLi.after(newMappingLi);
                    } else {
                        activeElement.find('ul.mappings').append(newMappingLi);
                    }
                }
                targetLi.removeClass('selected');
            });

            Omeka.closeSidebar(sidebar);
            sidebar.html(defaultSidebarHtml);
        });

        $(document).on('change', '.sidebar input, .sidebar select, .sidebar textarea', function() {
            var sidebarInput = $(this);
            sidebarInput.addClass('touched');
        });

        function resetActiveColumns() {
            activeElements = null;
            $('tr.mappable.active').removeClass('active');
        }

        /*
         * Actions on mapped columns.
         */

        // Remove mapping.
        $('.section').on('click', 'a.remove-mapping', function(e) {
            activeElement = $(e.target).closest('tr.mappable');
            e.preventDefault();
            e.stopPropagation();
            $(this).parents('li.mapping').remove();
            if((activeElement.find('ul.mappings li').length) === 0) {
                activeElement.find('.make_inherit').prop('checked', false);  
                activeElement.find('.make_inherit').prop('disabled', true);
            }
        });

        /*
         * Modified from resource-form.js in core
         */


        function applyMappings(flagName, flagValue, flagLiClass, flagLabel) {
            var hasFlag = activeElement.find('ul.mappings li.' + flagLiClass);
            if (flagValue == 'default') {
                if (hasFlag.length) {
                    hasFlag.remove();
                } else {
                    return;
                }
            }
            if (hasFlag.length) {
                var flagUnique = (flagLiClass !== 'property');
                if (flagUnique){
                    activeElement.find('ul.mappings .' + flagLiClass).remove();
                    hasFlag = activeElement.find('ul.mappings li.' + flagLiClass);
                }
            }

            if (hasFlag.length === 0) {
                var index = activeElement.data('element-id');
                flagName = flagName + "[" + index + "]";
                var newInput = $('<input type="hidden"></input>').attr('name', flagName).attr('value', flagValue);
                var newMappingLi = $('<li class="mapping ' + flagLiClass + '">' + flagLabel  + actionsHtml  + '</li>');
                newMappingLi.append(newInput);
                var existingMappingLi = activeElement.find('ul.mappings .' + flagLiClass).filter(':last');
                if (existingMappingLi.length) {
                    existingMappingLi.after(newMappingLi);
                } else {
                    activeElement.find('ul.mappings').append(newMappingLi);
                }
            }
        };
    });

})(jQuery);

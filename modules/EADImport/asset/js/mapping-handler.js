/**
 * Initially based on Omeka S omeka2importer.js and resource-core.js.
 */
(function ($) {

    $(document).ready(function () {
        var actionsHtml = '<ul class="actions">'
            + '<li><a aria-label="' + Omeka.jsTranslate('Remove mapping') + '" title="' + Omeka.jsTranslate('Remove mapping') + '" class="o-icon-delete remove-mapping" href="#" style="display: inline;"></a></li>'
            + '</ul>';

        var currentSidebar = $('#list-ead-mapping');
        var defaultSidebarHtml = currentSidebar.html();

        $('#list-mapping').on('click', function () {
            currentSidebar.addClass('active');
            resetBehavior();
        });

        currentSidebar.find('.sidebar-close').on('click', function () {
            var sidebar = $('#list-mapping');
            var inputsSelected = $('#mapping-loader li.selector-child.selected');
            inputsSelected.each(function () {
                $(this).removeClass('selected');
            });
            sidebar.removeClass('active');
            Omeka.closeSidebar(sidebar);
        });

        function resetBehavior() {

            if (!currentSidebar.hasClass('active')) {
                defaultSidebarHtml = currentSidebar.html();
            }

            $('#list-mapping-actions').hide();

            currentSidebar.find('.selector li.selector-parent').on('click', function (e) {
                e.stopPropagation();
                if ($(this).children('li')) {
                    $(this).toggleClass('show');
                }
            });
            $('#mapping-loader li.selector-child').on('click', function (e) {
                e.stopPropagation();
                $(this).addClass('selected');
            });
        };

        currentSidebar.find('.selector-child').on('click', function () {
            var mappingToApply = ($(this).attr('data-field-mapping'));
            var mappingId = ($(this).closest('.selector-parent').attr('data-mapping-id'));
            mappingToApply = JSON.parse(mappingToApply);
            var currentSection = $('fieldset.section.active');
            var currentSectionName = currentSection.attr('data-column');
            var activeElements = currentSection.find('.element.mappable');
            var sidebarMapping = $('#add-ead-mapping');

            activeElements.each(function () {
                var xpath = this.dataset.elementId;
                var activeElement = $(this);
                if (mappingToApply[xpath]) {
                    for (const propertyId of mappingToApply[xpath].properties) {
                        var isMakeInherit = mappingToApply[xpath].make_inherit ? true : false;
                        var inheritCheckbox = activeElement.find('.make_inherit');
                        inheritCheckbox.prop('disabled', !isMakeInherit);
                        inheritCheckbox.prop('checked', isMakeInherit);
                        var propertyLabel = sidebarMapping.find('[data-property-id=' + propertyId + ']').attr('data-child-search');
                        var newInput = $('<input type="hidden" name="nodes_mapping[' + currentSectionName + '][' + xpath + '][properties][]" ></input>');
                        newInput.val(propertyId);
                        var newMappingLi = $('<li class="mapping property" data-mapping-id="' + mappingId + '" data-property-id="' + propertyId + '">' + propertyLabel + actionsHtml + '</li>');
                        newMappingLi.append(newInput);
                        $(this).find('ul.mappings').append(newMappingLi);
                    };
                }
            });

        });
    });

})(jQuery);
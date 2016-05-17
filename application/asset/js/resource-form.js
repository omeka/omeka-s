(function($) {

    $(document).ready( function() {

        // Select property
        $('#property-selector li.selector-child').on('click', function(e) {
            e.stopPropagation();
            var property = $(this);
            var term = property.data('property-term');
            var field = $('[data-property-term = "' + term + '"].field');
            if (!field.length) {
                field = makeNewField(property);
            }
            $('#property-selector').removeClass('mobile');
            Omeka.scrollTo(field);
        });

        $('#resource-template-select').on('change', function(e) {
            // Restore the original property label and comment.
            $('.alternate').remove();
            $('.field-label, .field-description').show();
            rewritePropertyFields(true);
        });

        $('a.value-language:not(.active)').on('click', function(e) {
            var button = $(this);
            e.preventDefault();
            button.next('input.value-language').addClass('active').focus();
            if (!button.hasClass('active')) {
                button.addClass('active');
            }
        });

        // Make new value inputs whenever "add value" button clicked.
        $('#properties').on('click', '.add-value', function(e) {
            e.preventDefault();
            var typeButton = $(this);
            var field = typeButton.closest('.resource-values.field');
            var value = makeNewValue(field.data('property-term'), null, typeButton.data('type'))
            field.find('.values').append(value);
        });

        // Remove value.
        $('a.remove-value').on('click', function(e) {
            e.preventDefault();
            var thisButton = $(this);
            var value = thisButton.closest('.value');
            // Disable all form controls.
            value.find(':input').prop('disabled', true);
            value.addClass('delete');
            value.find('a.restore-value').show().focus();
            thisButton.hide();
        });

        // Restore a removed value
        $('a.restore-value').on('click', function(e) {
            e.preventDefault();
            var thisButton = $(this);
            var value = thisButton.closest('.value');
            // Enable all form controls.
            value.find('*').filter(':input').prop('disabled', false);
            value.removeClass('delete');
            value.find('a.remove-value').show().focus();
            thisButton.hide();
        });

        // Open or close item set
        $('a.o-icon-lock, a.o-icon-unlock').click(function(e) {
            e.preventDefault();
            var isOpenIcon = $(this);
            $(this).toggleClass('o-icon-lock').toggleClass('o-icon-unlock');
            var isOpenHiddenValue = $('input[name="o:is_open"]');
            if (isOpenHiddenValue.val() == 0) {
                isOpenIcon.attr('aria-label', 'Close icon set');
                isOpenIcon.attr('title', 'Close icon set');
                isOpenHiddenValue.attr('value', 1);
            } else {
                isOpenHiddenValue.attr('value', 0);
                isOpenIcon.attr('aria-label', 'Open icon set');
                isOpenIcon.attr('title', 'Open icon set');
            }
        });

        $('#select-item a').on('o:resource-selected', function (e) {
            var value = $('.value.selecting-resource');
            var valueObj = $('.resource-details').data('resource-values');
            var namePrefix = value.data('name-prefix');

            $(document).trigger('o:prepare-value', ['resource', value, valueObj, namePrefix]);
            Omeka.closeSidebar($('#select-resource'));
        });

        $('.button.resource-select').on('click', function(e) {
            e.preventDefault();
            var selectButton = $(this);
            var sidebar = $('#select-resource');
            var term = selectButton.closest('.resource-values').data('property-term');
            $('.selecting-resource').removeClass('selecting-resource');
            selectButton.closest('.value').addClass('selecting-resource');
            $('#select-item a').data('property-term', term);
            Omeka.populateSidebarContent(sidebar, selectButton.data('sidebar-content-url'));
            Omeka.openSidebar(sidebar);
        });

        $('.visibility [type="checkbox"]').on('click', function() {
            var publicCheck = $(this);
            if (publicCheck.prop("checked")) {
                publicCheck.attr('checked','checked');
            } else {
                publicCheck.removeAttr('checked');
            }
        });

        initPage();
    });

    /**
     * Make a new value.
     */
    var makeNewValue = function(term, valueObj, type) {
        var field = $('fieldset.resource-values.field[data-property-term="' + term + '"]');
        // Get the value node from the templates.
        if (typeof type !== 'string') {
            type = valueObj['type'];
        }
        var value = $('fieldset.value.template[data-data-type="' + type + '"]').clone(true);
        value.removeClass('template');

        // Prepare the value node.
        var count = field.find('fieldset.value').length;
        var namePrefix = field.data('property-term') + '[' + count + ']';
        value.data('name-prefix', namePrefix);
        value.find('input.property')
            .attr('name', namePrefix + '[property_id]')
            .val(field.data('property-id'));
        value.find('input.type')
            .attr('name', namePrefix + '[type]')
            .val(type);
        $(document).trigger('o:prepare-value', [type, value, valueObj, namePrefix]);

        return value;
    };

    /**
     * Prepare the markup for the default data types.
     */
    $(document).on('o:prepare-value', function(e, type, value, valueObj, namePrefix) {
        // Prepare simple single-value form inputs using data-value-key
        value.find(':input').each(function () {
            valueKey = $(this).data('valueKey');
            if (!valueKey) {
                return;
            }
            $(this).attr('name', namePrefix + '[' + valueKey + ']')
                .val(valueObj ? valueObj[valueKey] : null);
        });

        if (type === 'resource') {
            prepareResource(value, valueObj, namePrefix);
        }
    });

    /**
     * Prepare the markup for the resource data type.
     */
    var prepareResource = function(value, valueObj, namePrefix) {
        if (valueObj) {
            value.find('span.default').hide();
            var resource = value.find('.selected-resource');
            if (typeof valueObj['display_title'] === 'undefined') {
                valueObj['display_title'] = '[Untitled]';
            }
            resource.find('.o-title')
                .removeClass() // remove all classes
                .addClass('o-title ' + valueObj['value_resource_name'])
                .html($('<a>', {href: valueObj['url'], text: valueObj['display_title']}));
            if (typeof valueObj['thumbnail_url'] !== 'undefined') {
                resource.find('.o-title')
                    .prepend($('<img>', {src: valueObj['thumbnail_url']}));
            }
        }
    }

    /**
     * Make a new property field.
     */
    var makeNewField = function(property) {
        //sort out whether property is the LI that holds data, or the id
        var propertyLi, propertyId;

        switch (typeof property) {
            case 'object':
                propertyLi = property;
                propertyId = propertyLi.data('property-id');
            break;

            case 'number':
                propertyId = property;
                propertyLi = $('#property-selector').find("li[data-property-id='" + propertyId + "']");
            break;

            case 'string':
                propertyLi = $('#property-selector').find("li[data-property-term='" + property + "']");
                propertyId = propertyLi.data('property-id');
            break;
        }

        var term = propertyLi.data('property-term');
        var field = $('.resource-values.field.template').clone(true);
        field.removeClass('template');
        field.find('.field-label').text(propertyLi.data('child-search'));
        field.find('.field-term').text(term);
        field.find('.field-description').prepend(propertyLi.find('.field-comment').text());
        field.data('property-term', term);
        field.data('property-id', propertyId);
        // Adding the attr because selectors need them to find the correct field
        // and count when adding more.
        field.attr('data-property-term', term);
        field.attr('data-property-id', propertyId);
        $('div#properties').append(field);
        return field;
    };

    /**
     * Rewrite a property field following the rules defined by the selected
     * resource template.
     */
    var rewritePropertyField = function(template) {
        var properties = $('div#properties');
        var propertyId = template['o:property']['o:id'];
        var field = properties.find('fieldset[data-property-id="' + propertyId + '"]');

        if (field.length == 0) {
            field = makeNewField(propertyId);
        }

        if (template['o:data_type']) {
            // Use the single selector if the property has a data type.
            field.find('div.default-selector').hide();
            var singleSelector = field.find('div.single-selector');
            singleSelector.find('a.add-value.button').data('type', template['o:data_type'])
            singleSelector.show();

            // Add an empty value if none already exist in the property.
            if (!field.find('fieldset.value').length) {
                field.find('.values').append(makeNewValue(
                    field.data('property-term'), null, template['o:data_type']
                ));
            }
        } else {
            // Use the default selector if the property has no data type.
            field.find('div.single-selector').hide();
            field.find('div.default-selector').show();
        }

        var originalLabel = field.find('.field-label');
        if (template['o:alternate_label']) {
            var altLabel = originalLabel.clone();
            altLabel.addClass('alternate');
            altLabel.text(template['o:alternate_label']);
            altLabel.insertAfter(originalLabel);
            originalLabel.hide();
        }

        var originalDescription = field.find('.field-description');
        if (template['o:alternate_comment']) {
            var altDescription = originalDescription.clone();
            altDescription.addClass('alternate');
            altDescription.text(template['o:alternate_comment']);
            altDescription.insertAfter(originalDescription);
            originalDescription.hide();
        }

        properties.prepend(field);
    };

    /**
     * Rewrite all property fields following the rules defined by the selected
     * resource template.
     */
    var rewritePropertyFields = function(changeClass) {
        var templateSelect = $('#resource-template-select');
        var templateId = templateSelect.val();
        var fields = $('#properties fieldset.resource-values');
        if (!templateId) {
            // Using the default resource template, so the resource class must
            // be null and all properties should use the default selector.
            $('#resource-class-select').val(null);
            fields.find('div.single-selector').hide();
            fields.find('div.default-selector').show();
            return;
        }
        var url = templateSelect.data('api-base-url') + '/' + templateId;
        $.get(url)
            .done(function(data) {
                if (changeClass) {
                    // Change the resource class.
                    var classSelect = $('#resource-class-select');
                    if (data['o:resource_class'] && classSelect.val() === '') {
                        classSelect.val(data['o:resource_class']['o:id']);
                    }
                }

                // Rewrite every property field defined by the template. We
                // reverse the order so property fields on page that are not
                // defined by the template are ultimately appended.
                var templatePropertyIds = data['o:resource_template_property']
                    .reverse().map(function(templateProperty) {
                        rewritePropertyField(templateProperty);
                        return templateProperty['o:property']['o:id'];
                    });
                // Property fields that are not defined by the template should
                // use the default selector.
                fields.each(function() {
                    var propertyId = $(this).data('property-id');
                    if (templatePropertyIds.indexOf(propertyId) === -1) {
                        var field = $(this);
                        field.find('div.single-selector').hide();
                        field.find('div.default-selector').show();
                    }
                });
            })
            .fail(function() {
                console.log('Failed loading resource template from API');
            });
    }

    /**
     * Initialize the page.
     */
    var initPage = function() {
        if (typeof valuesJson == 'undefined') {
            makeNewField('dcterms:title');
            makeNewField('dcterms:description');
        } else {
            $.each(valuesJson, function(term, valueObj) {
                var field = makeNewField(term);
                $.each(valueObj.values, function(index, value) {
                    field.find('.values').append(makeNewValue(term, value));
                });
            });
        }

        rewritePropertyFields(false);

        $('input.value-language').each(function() {
            var languageInput = $(this);
            if (languageInput.val() !== "") {
                languageInput.addClass('active');
                languageInput.prev('a.value-language').addClass('active');
            }
        });
    };
})(jQuery);


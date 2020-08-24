(function($) {

    $(document).ready( function() {

        if ($('div#properties').data('default-data-types').split(',').length < 1) {
            $('div#properties').data('default-data-types', 'literal,resource,uri');
        }

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
            applyResourceTemplate(true);
        });

        $('a.value-language').on('click', function(e) {
            e.preventDefault();
            var languageButton = $(this);
            var languageInput = languageButton.next('input.value-language');
            var languageSelect = languageInput.next('select.value-language');
            languageButton.toggleClass('active');
            if (languageButton.hasClass('active')) {
                if (languageButton.closest('.resource-values.field').data('allowed-languages')) {
                    languageInput.removeClass('active');
                    languageSelect.addClass('active').focus();
                } else {
                    languageInput.addClass('active').focus();
                    languageSelect.removeClass('active');
                }
            } else {
                languageInput.removeClass('active');
                languageSelect.removeClass('active');
            }
        });

        $('input.value-language').on('keyup', function(e) {
            if ('' === this.value || Omeka.langIsValid(this.value)) {
                this.setCustomValidity('');
            } else {
                this.setCustomValidity(Omeka.jsTranslate('Please enter a valid language tag'))
            }
        });

        // Make new value inputs whenever "add value" button clicked.
        $('#properties').on('click', '.add-value', function(e) {
            e.preventDefault();
            var typeButton = $(this);
            var field = typeButton.closest('.resource-values.field');
            var value = makeNewValue(field.data('property-term'), typeButton.data('type'))
            field.find('.values').append(value);
            if (field.data('autocomplete')) {
                value.find('textarea.input-value').addClass('autocomplete');
                value.find('textarea.input-value.autocomplete').each(initAutocomplete);
            }
            value.find('input.value-language').each(initValueLanguage);
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
                isOpenIcon.attr('aria-label', Omeka.jsTranslate('Close item set'));
                isOpenIcon.attr('title', Omeka.jsTranslate('Close item set'));
                isOpenHiddenValue.attr('value', 1);
            } else {
                isOpenHiddenValue.attr('value', 0);
                isOpenIcon.attr('aria-label', Omeka.jsTranslate('Open item set'));
                isOpenIcon.attr('title', Omeka.jsTranslate('Open item set'));
            }
        });

        $('#select-item a').on('o:resource-selected', function (e) {
            var value = $('.value.selecting-resource');
            var valueObj = $('.resource-details').data('resource-values');

            $(document).trigger('o:prepare-value', ['resource', value, valueObj]);
            Omeka.closeSidebar($('#select-resource'));
        });

        // Prevent resource details from opening when quick add is toggled on.
        $('#select-resource').on('click', '.quick-select-toggle', function() {
            $('#item-results').find('a.select-resource').each(function() {
                $(this).toggleClass('sidebar-content');
            });
        });

        $('#select-resource').on('o:resources-selected', '.select-resources-button', function(e) {
            var value = $('.value.selecting-resource');
            var field = value.closest('.resource-values.field');
            $('#item-results').find('.resource')
                .has('input.select-resource-checkbox:checked').each(function(index) {
                    if (0 < index) {
                        value = makeNewValue(field.data('property-term'), 'resource');
                        field.find('.values').append(value);
                    }
                    var valueObj = $(this).data('resource-values');
                    $(document).trigger('o:prepare-value', ['resource', value, valueObj]);
                });
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

        // Handle validation for required properties.
        $('form.resource-form').on('submit', function(e) {

            var thisForm = $(this);
            var errors = [];

            // Iterate all required properties.
            var requiredProps = thisForm.find('.resource-values.required');
            requiredProps.each(function() {

                var thisProp = $(this);
                var propIsCompleted = false;

                // Iterate all values for this required property.
                var requiredValues = $(this).find('.value').not('.delete');
                requiredValues.each(function() {

                    var thisValue = $(this);
                    var valueIsCompleted = true;

                    // All inputs of this value with the "to-require" class must
                    // be completed when the property is required.
                    var toRequire = thisValue.find('.to-require');
                    toRequire.each(function() {
                        if ('' === $.trim($(this).val())) {
                            // Found an incomplete input.
                            valueIsCompleted = false;
                        }
                    });
                    if (valueIsCompleted) {
                        // There's at least one completed value of this required
                        // property. Consider the requirement satisfied.
                        propIsCompleted = true;
                        return false; // break out of each
                    }
                });
                if (!propIsCompleted) {
                    // No completed values found for this required property.
                    var propLabel = thisProp.find('.field-label').text();
                    errors.push('The following field is required: ' + propLabel);
                }
            });
            if (errors.length) {
                e.preventDefault();
                alert(errors.join("\n"));
            }

            $('#values-json').val(JSON.stringify(collectValues()));
        });

        initPage();
    });

    var collectValues = function () {
        var values = {};
        $('#properties').children().each(function () {
            var propertyValues = [];
            var property = $(this);
            var propertyTerm = property.data('propertyTerm');
            var propertyId = property.data('propertyId');
            property.find('.values > .value').each(function () {
                var valueData = {}
                var value = $(this);
                if (value.hasClass('delete')) {
                    return;
                }
                valueData['property_id'] = propertyId;
                valueData['type'] = value.data('dataType');
                valueData['is_public'] = value.find('input.is_public').val();
                value.find(':input[data-value-key]').each(function () {
                    var input = $(this);
                    var valueKey = input.data('valueKey');
                    if (!valueKey || input.prop('disabled')) {
                        return;
                    }
                    valueData[valueKey] = input.val();
                });
                propertyValues.push(valueData);
            });
            if (propertyValues.length) {
                values[propertyTerm] = values.hasOwnProperty(propertyTerm)
                    ? values[propertyTerm].concat(propertyValues)
                    : propertyValues;
            }
        });
        return values;
    };

    /**
     * Make a new value.
     */
    var makeNewValue = function(term, dataType, valueObj) {
        // Get the value node from the templates.
        if (!dataType || typeof dataType !== 'string') {
            dataType = valueObj ? valueObj['type'] : '';
        }
        // In form resource fields, data-types is plural, but in template and value, it is singular. The button uses "type".
        var field = $('.resource-values.field[data-property-term="' + term + '"]' + (dataType ? '[data-data-types="' + dataType + '"]' : ''));
        var value = $('.value.template[data-data-type="' + dataType + '"]').clone(true);
        value.removeClass('template');

        // Get and display the value's visibility.
        var isPublic = true; // values are public by default
        if (field.hasClass('private') || (valueObj && false === valueObj['is_public'])) {
            isPublic = false;
        }
        var valueVisibilityButton = value.find('a.value-visibility');
        if (isPublic) {
            valueVisibilityButton.removeClass('o-icon-private').addClass('o-icon-public');
            valueVisibilityButton.attr('aria-label', Omeka.jsTranslate('Make private'));
            valueVisibilityButton.attr('title', Omeka.jsTranslate('Make private'));
        } else {
            valueVisibilityButton.removeClass('o-icon-public').addClass('o-icon-private');
            valueVisibilityButton.attr('aria-label', Omeka.jsTranslate('Make public'));
            valueVisibilityButton.attr('title', Omeka.jsTranslate('Make public'));
        }
        // Prepare the value node.
        var valueLabelID = 'property-' + field.data('property-id') + '-label';
        value.find('input.is_public')
            .val(isPublic ? 1 : 0);
        value.find('span.label')
            .attr('id', valueLabelID);
        value.find('textarea.input-value')
            .attr('aria-labelledby', valueLabelID);
        value.attr('aria-labelledby', valueLabelID);
        $(document).trigger('o:prepare-value', [dataType, value, valueObj]);

        return value;
    };

    /**
     * Prepare the markup for the default data types.
     */
    $(document).on('o:prepare-value', function(e, dataType, value, valueObj) {
        // Prepare simple single-value form inputs using data-value-key
        value.find(':input').each(function () {
            var valueKey = $(this).data('valueKey');
            if (!valueKey) {
                return;
            }
            $(this).removeAttr('name')
                .val(valueObj ? valueObj[valueKey] : null);
        });

        // Prepare the markup for the resource data types.
        var resourceDataTypes = [
            'resource',
            'resource:item',
            'resource:itemset',
            'resource:media',
        ];
        if (valueObj && -1 !== resourceDataTypes.indexOf(dataType)) {
            value.find('span.default').hide();
            var resource = value.find('.selected-resource');
            if (typeof valueObj['display_title'] === 'undefined') {
                valueObj['display_title'] = Omeka.jsTranslate('[Untitled]');
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
    });

    /**
     * Make a new property field with data stored in the property selector.
     */
    var makeNewField = function(property, dataTypes) {
        // Prepare data type name of the field.
        if (!dataTypes || dataTypes.length < 1) {
            dataTypes = $('#properties').data('default-data-types').split(',');
        }

        // Sort out whether property is the LI that holds data, or the id.
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

            default:
                return null;
        }

        var term = propertyLi.data('property-term');
        var field = $('.resource-values.field.template').clone(true);
        field.removeClass('template');
        field.find('.field-label').text(propertyLi.data('child-search')).attr('id', 'property-' + propertyId + '-label');
        field.find('.field-term').text(term);
        field.find('.field-description').prepend(propertyLi.find('.field-comment').text());
        field.data('property-term', term);
        field.data('property-id', propertyId);
        field.data('data-types', dataTypes.join(','));
        // Adding the attr because selectors need them to find the correct field
        // and count when adding more.
        field.attr('data-property-term', term);
        field.attr('data-property-id', propertyId);
        field.attr('data-data-types', dataTypes.join(','));
        field.attr('aria-labelledby', 'property-' + propertyId + '-label');
        $('div#properties').append(field);

        new Sortable(field.find('.values')[0], {
            draggable: '.value',
            handle: '.sortable-handle'
        });

        field.trigger('o:property-added');
        return field;
    };

    /**
     * Rewrite an existing property field, or create a new one, following the
     * rules defined by the selected resource property template.
     */
    var rewritePropertyField = function(templateProperty) {
        var templateId = $('#resource-template-select').val();
        var properties = $('div#properties');
        var propertyId = templateProperty['o:property']['o:id'];
        var dataTypes = templateProperty['o:data_type'];

        // Check if an existing field exists in order to update it and to avoid duplication.
        // Since fields can have the same property but different data types,
        // a check is done on each field to find the good one, if any.
        var field;
        var fields = properties.find('[data-property-id="' + propertyId + '"]');
        if (fields.length > 0) {
            var useDefaultDataTypes = dataTypes.length < 1;
            var dataTypesSorted = dataTypes.sort();
            fields.each(function () {
                var dataTypesElements = $(this).data('data-types').split(',').sort();
                if (useDefaultDataTypes) {
                    if (dataTypesElements.join(',') === $('div#properties').data('default-data-types').split(',').sort().join(',')){
                        field = $(this);
                        return false;
                    }
                } else if (dataTypesSorted.length === dataTypesElements.length
                    && dataTypesSorted.every(function(value, index) { return value === dataTypesElements[index]})
                ) {
                    field = $(this);
                    field.data('data-types', dataTypes.join(','));
                    return false;
                }
            });
        }
        if (!field) {
            field = makeNewField(propertyId, dataTypes);
        }
        var originalLabel = field.find('.field-label');
        var originalDescription = field.find('.field-description');
        var defaultSelector = field.find('div.default-selector');
        var multipleSelector = field.find('div.multiple-selector');
        var singleSelector = field.find('div.single-selector');

        if (templateProperty['o:is_required']) {
            field.addClass('required');
        }
        if (templateProperty['o:is_private']) {
            field.addClass('private');
        }
        if (templateProperty['o:alternate_label']) {
            var altLabel = originalLabel.clone();
            var altLabelId = 'property-' + propertyId + '-' + dataTypes.join('-') + '-label';
            altLabel.addClass('alternate');
            altLabel.text(templateProperty['o:alternate_label']);
            altLabel.insertAfter(originalLabel);
            altLabel.attr('id', altLabelId);
            field.attr('aria-labelledby', altLabelId);
            originalLabel.hide();
        }
        if (templateProperty['o:alternate_comment']) {
            var altDescription = originalDescription.clone();
            altDescription.addClass('alternate');
            altDescription.text(templateProperty['o:alternate_comment']);
            altDescription.insertAfter(originalDescription);
            originalDescription.hide();
        }

        // Remove any unchanged default values for this property so we start fresh.
        field.find('.value.default-value').remove();

        // Change value selector (multiple, single, or default).
        if (templateProperty['o:data_type'].length > 1) {
            defaultSelector.hide();
            singleSelector.hide();
            if (!multipleSelector.find('.add-value').length) {
                multipleSelector.append(prepareMultipleSelector(templateProperty['o:data_type']));
            }
            multipleSelector.show();
        } else if (templateProperty['o:data_type'].length === 1) {
            defaultSelector.hide();
            multipleSelector.hide();
            singleSelector.find('a.add-value.button').data('type', templateProperty['o:data_type'][0]);
            singleSelector.show();
        } else {
            multipleSelector.hide();
            singleSelector.hide();
            defaultSelector.show();
        }

        field.data('template-id', templateId);
        field.attr('data-template-id', templateId);

        properties.prepend(field);
    };

    /**
     * Prepare a selector (usualy a html list of buttons) from a list of data types.
     *
     * @see view/common/resource-form-templates.phtml
     *
     * @param array dataTypes
     * @return string
     */
    var prepareMultipleSelector = function(dataTypes) {
        var html = '';
        dataTypes.forEach(function(dataType) {
            var dataTypeTemplate = $('.template.value[data-data-type="' + dataType + '"]');
            var label = dataTypeTemplate.data('data-type-label') ? dataTypeTemplate.data('data-type-label') : Omeka.jsTranslate('Add value');
            var icon = dataTypeTemplate.data('data-type-icon') ? dataTypeTemplate.data('data-type-icon') : dataType.substring(0, (dataType + ':').indexOf(':'));
            html += dataTypeTemplate.data('data-type-button')
                ? dataTypeTemplate.data('data-type-button')
                : '<a href="#" class="add-value button o-icon-' + icon + '" data-type="' + dataType + '">' + label + '</a>';
        });
        return html;
    };

    /**
     * Apply the selected resource template to the form.
     *
     * @param bool changeClass Whether to change the suggested class
     */
    var applyResourceTemplate = function(changeClass) {
        // Fieldsets may have been marked as required or private in a previous state.
        $('.field').removeClass('required');
        $('.field').removeClass('private');

        var templateSelect = $('#resource-template-select');
        var templateId = templateSelect.val();
        var fields = $('#properties .resource-values');

        var finalize = function(resourceTemplate) {
            // Remove empty properties, except the templates ones.
            // TODO Keep properties selected by user (remove the "default-value"?).
            var filteredFields = templateId ? $('#properties .resource-values[data-template-id!="' + templateId + '"]') : $('#properties .resource-values');
            filteredFields.each(function() {
                if ($(this).find('.inputs .values > .value').length === $(this).find('.inputs .values > .value.default-value').length) {
                    $(this).remove();
                }
            });

            // Add default fields if none.
            if (!$('#properties .resource-values').length) {
                makeDefaultTemplate();
            }

            // Reset all settings, then prepare specific settings according to template.
            // Reset autocomplete for all properties.
            fields.removeData('autocomplete');
            fields.find('.inputs .values textarea.input-value.autocomplete').each(function() {
                var autocomp = $(this).autocomplete();
                if (autocomp) {
                    autocomp.dispose();
                }
            });
            fields.find('.inputs .values textarea.input-value').prop('autocomplete', 'off').removeClass('autocomplete');
            // Reset languages select for all properties, but keep currently selected values.
            fields.find('.inputs .values input.value-language').each(function() {
                if ($(this).closest('.resource-values.field').data('allowed-languages')) {
                    $(this).val($(this).next('select.value-language').val());
                }
            });
            fields.removeData('allowed-languages');
            fields.removeData('no-language');
            fields.find('.inputs .values input.value-language, .inputs .values select.value-language').removeClass('active');
            fields.find('.inputs .values a.value-language').removeClass('no-language');

            // Prepare specific data for each property.
            fields.each(function() {
                var field = $(this);
                var propertyId = Number(field.data('property-id'));
                var hasTemplateProperties = resourceTemplate && resourceTemplate['o:resource_template_property'].length;
                // Add an empty value if none already exist in the property and fill
                // it with the default value set in the resource template, if any.
                if (!field.find('.inputs .values > .value').length) {
                    var defaultValueObj = null;
                    if (hasTemplateProperties) {
                        resourceTemplate['o:resource_template_property'].some(function(rtp) {
                            if (rtp['o:property']['o:id'] === propertyId && rtp['o:settings']['default_value'] && rtp['o:settings']['default_value'].length) {
                                defaultValueObj = {
                                    '@value': rtp['o:settings']['default_value'],
                                    'is_public': !rtp['o:is_private'],
                                    'property_id': propertyId,
                                    'type': 'literal',
                                };
                                return true;
                            }
                        });
                    }
                    field.find('.inputs .values').append(
                        makeDefaultValue(field.data('property-term'), field.data('data-types').substring(0, (field.data('data-types') + ',').indexOf(',')), defaultValueObj)
                    );
                }
                // Apply first the template settngs, then the property ones.
                if (resourceTemplate) {
                    if (resourceTemplate['o:settings']['autocomplete'] && $.inArray(resourceTemplate['o:settings']['autocomplete'], ['sw', 'in']) > -1) {
                        field.data('autocomplete', resourceTemplate['o:settings']['autocomplete']);
                        field.find('.inputs .values textarea.input-value').addClass('autocomplete');
                    }
                    if (resourceTemplate['o:settings']['no_language'] > 0) {
                        field.data('no-language', '1');
                    } else if (resourceTemplate['o:settings']['allowed_languages'] && resourceTemplate['o:settings']['allowed_languages'].length) {
                        field.data('allowed-languages', resourceTemplate['o:settings']['allowed_languages'].join(','));
                    }
                    if (hasTemplateProperties) {
                        resourceTemplate['o:resource_template_property'].some(function(rtp) {
                            if (rtp['o:property']['o:id'] === propertyId) {
                                if (rtp['o:settings']['autocomplete'] && $.inArray(rtp['o:settings']['autocomplete'], ['sw', 'in']) > -1) {
                                    field.data('autocomplete', rtp['o:settings']['autocomplete']);
                                    field.find('.inputs .values textarea.input-value').addClass('autocomplete');
                                }
                                if (rtp['o:settings']['no_language'] > 0) {
                                    field.data('no-language', '1');
                                } else if (rtp['o:settings']['allowed_languages'] && rtp['o:settings']['allowed_languages'].length) {
                                    field.removeData('no-language');
                                    field.data('allowed-languages', rtp['o:settings']['allowed_languages'].join(','));
                                }
                                return true;
                            }
                        });
                    }
                }
                // Initialize settings filtered by selectors.
                fields.find('.inputs .values textarea.input-value.autocomplete').each(initAutocomplete);
                fields.find('.inputs .values input.value-language').each(initValueLanguage);
            });
        };

        if (!templateId) {
            // Using the default resource template, so all properties should use the default
            // selector.
            fields.data('template-id', '');
            fields.attr('data-template-id', '');
            fields.find('div.multiple-selector').hide();
            fields.find('div.single-selector').hide();
            fields.find('div.default-selector').show();
            // Merge all duplicate properties, keeping order of values when possible.
            fields.each(function() {
                var propertyId = $(this).attr('data-property-id');
                // Deduplicate only first properties, that are not already processed.
                if ($(this).prevAll('[data-property-id="' + propertyId + '"]').length < 1) {
                    var duplicatedFields = $('div#properties').find('[data-property-id="' + propertyId + '"]');
                    var duplicatedFieldFirst = duplicatedFields.first();
                    duplicatedFields.each(function(index) {
                        if (index > 0) {
                            duplicatedFieldFirst.find('.inputs .values').append($(this).find('.inputs .values > .value'));
                            $(this).remove();
                        }
                    });
                }
            });
            finalize();
            return;
        }

        var url = templateSelect.data('api-base-url') + '/' + templateId;
        return $.get(url)
            .done(function(data) {
                if (changeClass) {
                    // Change the resource class.
                    var classSelect = $('#resource-class-select');
                    if (data['o:resource_class'] && classSelect.val() === '') {
                        classSelect.val(data['o:resource_class']['o:id']);
                        classSelect.trigger('chosen:updated');
                    }
                }

                // Rewrite every property field defined by the template. We
                // reverse the order so property fields on page that are not
                // defined by the template are ultimately appended.
                data['o:resource_template_property']
                    .reverse().map(function(templateProperty) {
                        rewritePropertyField(templateProperty);
                    });

                // Property fields that are not defined by the template should
                // use the default selector and should be merged.
                var otherFields = $('#properties .resource-values').filter('[data-template-id!="' + templateId + '"]');
                otherFields.data('template-id', '');
                otherFields.attr('data-template-id', '');
                otherFields.data('data-types', $('div#properties').data('default-data-types'));
                otherFields.attr('data-data-types', $('div#properties').data('default-data-types'));
                otherFields.find('div.multiple-selector').hide();
                otherFields.find('div.single-selector').hide();
                otherFields.find('div.default-selector').show();
                // Merge all duplicate properties not in the current template, keeping order of values.
                otherFields.each(function() {
                    var propertyId = $(this).attr('data-property-id');
                    // Deduplicate only first properties, that are not already processed.
                    if ($(this).prevAll('[data-template-id!="' + templateId + '"][data-property-id="' + propertyId + '"]').length < 1) {
                        var duplicatedFields = $('div#properties').find('[data-template-id!="' + templateId + '"][data-property-id="' + propertyId + '"]');
                        var duplicatedFieldFirst = duplicatedFields.first();
                        duplicatedFields.each(function(index) {
                            if (index > 0) {
                                duplicatedFieldFirst.find('.inputs .values').append($(this).find('.inputs .values > .value'));
                                $(this).remove();
                            }
                        });
                    }
                });

                // Furthermore, the values are moved to the property row according
                // to their data type when there are multiple duplicate properties.
                // @see \Omeka\Api\Representation\AbstractEntityRepresentation::values()
                fields = $('#properties .resource-values');
                if (fields.length > 0) {
                    // Prepare the list of data types one time and make easier to fill specific rows first.
                    var dataTypesByProperty = {};
                    fields.each(function() {
                        var fieldTemplateId = $(this).data('template-id');
                        var propertyId = $(this).data('property-id');
                        var dataTypes = $(this).data('data-types');
                        if (!dataTypesByProperty.hasOwnProperty(propertyId)) {
                            dataTypesByProperty[propertyId] = {};
                        }
                        if (fieldTemplateId && dataTypes.split(',').length) {
                            dataTypes.split(',').forEach(function(dataType) {
                                if (dataType === 'resource') {
                                    dataTypesByProperty[propertyId]['resource'] = dataTypes;
                                    dataTypesByProperty[propertyId]['resource:item'] = dataTypes;
                                    dataTypesByProperty[propertyId]['resource:itemset'] = dataTypes;
                                    dataTypesByProperty[propertyId]['resource:media'] = dataTypes;
                                } else {
                                    dataTypesByProperty[propertyId][dataType] = dataTypes;
                                }
                            });
                        } else {
                            dataTypesByProperty[propertyId]['default'] = $('div#properties').data('default-data-types');
                        }
                    });
                    fields.each(function() {
                        var propertyId = $(this).data('property-id');
                        $(this).find('.inputs .values > .value').each(function() {
                            var valueDataType = $(this).data('data-type');
                            if (!dataTypesByProperty[propertyId].hasOwnProperty(valueDataType)) {
                                if (!dataTypesByProperty[propertyId].hasOwnProperty('default')) {
                                    return;
                                }
                                valueDataType = 'default';
                            }
                            fields
                                .filter('[data-property-id="' + propertyId + '"][data-data-types="' + dataTypesByProperty[propertyId][valueDataType] + '"]')
                                .find('.inputs .values')
                                .append($(this));
                        });
                    });
                }

                finalize(data);
            })
            .fail(function() {
                console.log('Failed loading resource template from API');
            });
    }

    var makeDefaultValue = function (term, dataType, valueObj) {
        return makeNewValue(term, dataType, valueObj)
            .addClass('default-value')
            .one('change', '*', function (event) {
                $(event.delegateTarget).removeClass('default-value');
            });
    };

    var makeDefaultTemplate = function() {
        var defaultDataType = $('div#properties').data('default-data-types').substring(0, ($('div#properties').data('default-data-types') + ',').indexOf(','));
        makeNewField('dcterms:title').find('.values')
            .append(makeDefaultValue('dcterms:title', defaultDataType));
        makeNewField('dcterms:description').find('.values')
            .append(makeDefaultValue('dcterms:description', defaultDataType));
    }

    var initAutocomplete = function() {
        var searchField = $(this);
        searchField.autocomplete({
            serviceUrl: searchField.data('autocomplete-url'),
            dataType: 'json',
            paramName: 'q',
            params: {
                prop: searchField.closest('.resource-values.field').data('property-id'),
                type: searchField.closest('.resource-values.field').data('autocomplete'),
                output: 'autocomplete',
            }
        });
    }

    var initValueLanguage = function() {
        var languageInput = $(this);
        var languageElement;
        var language = languageInput.val();
        var languageButton = languageInput.prev('a.value-language');
        var languageSelect = languageInput.next('select.value-language');
        var field = languageInput.closest('.resource-values.field');
        if (field.data('no-language')) {
            language = '';
            languageButton.removeClass('active').addClass('no-language');
            languageInput.prop('disabled', true).removeClass('active');
            languageSelect.find('option').remove().end().prop('disabled', true).removeClass('active');
        } else if (field.data('allowed-languages')) {
            languageButton.removeClass('no-language');
            languageInput.prop('disabled', true).removeClass('active');
            languageSelect.prop('disabled', false)
                .find('option').remove().end()
                .append($('<option/>', {value: '',  text: ''}));
            $.each(field.data('allowed-languages').split(','), function (index, value) {
                languageSelect.append($('<option/>', {value: value,  text: value}));
            });
            // Check if the existing language is listed in the select in order
            // to keep languages set before applying the template.
            if (language.length) {
                var hasLanguage = false;
                languageSelect.find('option').each(function(){
                    if (this.value == language) {
                        hasLanguage = true;
                        return false;
                    }
                });
                if (!hasLanguage) {
                    languageSelect.append($('<option/>', {value: language, text: language + ' ' + '[unspecified]'}));
                }
            }
            languageSelect.val(language);
            languageElement = languageSelect;
        } else {
            languageButton.removeClass('no-language');
            languageInput.prop('disabled', false);
            languageSelect.find('option').remove().end().prop('disabled', true).removeClass('active');
            languageElement = languageInput;
        }
        if (language !== '') {
            languageButton.addClass('active');
            languageElement.addClass('active');
        }
    }

    /**
     * Initialize the page.
     */
    var initPage = function() {
        // Create the form for a generic resource.
        if (typeof valuesJson == 'undefined') {
            makeDefaultTemplate();
        } else {
            $.each(valuesJson, function(term, valueObj) {
                var field = makeNewField(term);
                $.each(valueObj.values, function(index, value) {
                    field.find('.values').append(makeNewValue(term, null, value));
                });
            });
        }

        // Adapt the form for the template, if any.
        var applyTemplateClass = $('body').hasClass('add');
        $.when(applyResourceTemplate(applyTemplateClass)).done(function () {
            $('#properties').closest('form').trigger('o:form-loaded');
        });

        $('.inputs .values textarea.input-value').prop('autocomplete', 'off');

        $('input.value-language').each(initValueLanguage);
    };
})(jQuery);

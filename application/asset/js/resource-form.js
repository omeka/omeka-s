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
            Omeka.scrollTo(field);
        });

        //handle changing the resource template
        $('#resource-template-select').on('change', function(e) {
            var templateId = $(this).val();
            $('.alternate').remove();
            $('.field-label, .field-description').show();
            if (templateId == '') {
                return;
            }
            var url = $(this).data('api-base-url') + '/' + templateId;
            $.ajax({
                'url': url,
                'type': 'get'
            }).done(function(data) {
                var classSelect = $('select#resource-class-select');
                if (data['o:resource_class'] && classSelect.val() == '' ) {
                    classSelect.val(data['o:resource_class']['o:id']);
                }
                //in case people have added fields, reverse the template so
                //I can prepend everything and keep the order, and then drop
                //back to what people have added
                var propertyTemplates = data['o:resource_template_property'].reverse(); 
                propertyTemplates.forEach(rewritePropertyFromTemplateProperty);
            }).error(function() {
                console.log('fail');
            });
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
        $('.add-value').on('click', function(e) {
            e.preventDefault();
            var wrapper = $(this).parents('.resource-values.field');
            var type = $(this).attr('class').replace(/o-icon-/, '').replace(/button/, '').replace(/add-value/, '').replace(/ /g, '');
            var qName = wrapper.data('property-term');
            $('fieldset.resource-values.field[data-property-term="' + qName + '"] .values').append(makeNewValue(qName, false, type));
        });

        // Remove value.
        $('a.remove-value').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var valueToRemove = $(this).parents('.value');

            valueToRemove.find('input, textarea').prop('disabled', true);
            valueToRemove.addClass('delete');
            valueToRemove.find('a.restore-value').show().focus();
            $(this).hide();
        });

        // Restore a removed value
        $('a.restore-value').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var valueToRemove = $(this).parents('.value');
            valueToRemove.find('a.remove-value').show().focus();
            valueToRemove.find('input').prop('disabled', false);
            valueToRemove.find('textarea').prop('disabled', false);
            valueToRemove.removeClass('delete');
            valueToRemove.find('input.delete').remove();
            $(this).hide();
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
            var propertyQname = $(this).data('property-term');
            var valuesData = $('.resource-details').data('resource-values');
            $('.value.selecting-resource').replaceWith(makeNewValue(propertyQname, valuesData));
        });

        $('.button.resource-select').on('click', function(e) {
            e.preventDefault();
            var context = $(this);
            // put 'selecting-resource' class on one value so that when the resource
            // is selected I can get rid of the placeholder
            $('.selecting-resource').removeClass('selecting-resource');
            context.parents('.value').addClass('selecting-resource');
            var qName = context.parents('.resource-values').data('property-term');
            $('#select-item a').data('property-term', qName);
            Omeka.openSidebar(context, "#select-resource");
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

    var _makeNewValue = function(term, value, valueType) {
        var valuesWrapper = $('fieldset.resource-values.field[data-property-term="' + term + '"]');
        var count = valuesWrapper.find('input.property').length;
        var propertyId = valuesWrapper.data('property-id');
        var languageElementName = term + '[' + count + '][@language]';
        if (typeof value !== 'undefined' && typeof valueType === 'undefined') {
            valueType = valueObjectType(value);
        }

        var newValue = $('.value.template.' + valueType).clone(true);
        newValue.removeClass('template');
        newValue.data('base-name', term + '[' + count + ']');

        var propertyInput = newValue.find('input.property');
        propertyInput.attr('name', term + '[' + count + '][property_id]');

        if (typeof value !== 'undefined' && typeof value['property_id'] !== 'undefined') {
            propertyInput.val(value['property_id']);
        } else {
            propertyInput.val(propertyId);
        }
        
        // set up text inputs
        var valueTextarea = newValue.find('textarea');
        var languageLabel = newValue.find('label.value-language');
        var languageInput = newValue.find('input.value-language');
        valueTextarea.attr('name', term + '[' + count + '][@value]');
        languageLabel.attr('for', languageElementName); // 
        languageInput.attr('name', languageElementName).attr('id', languageElementName);

        //set up uri inputs
        var uriInput = newValue.find('input.value');
        var uriInputLabel = newValue.find('label.value');
        var uriInputId = term + '[' + count + '][@id]';
        var uriLabelId = term + '[' + count + '][o:uri_label]'
        uriInput.attr('name', uriInputId).attr('id', uriInputId);
        uriInputLabel.attr('for', uriInputId);
        var uriLabel = newValue.find('textarea.value-label');
        var uriLabelLabel = newValue.find('label.value-label');
        uriLabel.attr('name', uriLabelId).attr('id', uriLabelId);
        uriLabelLabel.attr('for', uriLabelId);

        var showRemoveValue = false;
        if (typeof value !== 'undefined') {
            showRemoveValue = true;

            switch (valueType) {
                case 'literal' :
                    valueTextarea.val(value['@value']);
                    languageInput.val(value['@language']);
                break;

                case 'resource' :
                    var valueInternalInput = newValue.find('input.value');
                    var newResource = newValue.find('.selected-resource');
                    var title = value['display_title'];
                    if (typeof value['value_resource_id'] === 'undefined') {
                        break;
                    }

                    newResource.prev('.default').hide();
                    if (typeof title === 'undefined') {
                        title = '[Untitled]';
                    }
                    var link = $('<a></a>', {
                        href: value['url'],
                        text: title
                    });
                    if (value['thumbnail_url'] !== undefined) {
                        var thumbnail = '<img src="' + value['thumbnail_url'] + '">';
                        newResource.find('.o-title').prepend(thumbnail);
                    }

                    newResource.find('.o-title').append(link).addClass(value['value_resource_name']);

                    valueInternalInput.attr('name', term + '[' + count + '][value_resource_id]');
                    valueInternalInput.val(value['value_resource_id']);
                break;

                case 'uri' :
                    uriInput.val(value['@id']);
                    uriLabel.val(value['o:uri_label']);
                break;
            }
        }

        return newValue; 
    };

    var makeNewValue = function(field, valueObj) {
        // Get the value node from the templates.
        var type = valueObjectType(valueObj);
        var value = $('fieldset.value.template[data-data-type="' + type + '"]').clone(true);
        value.removeClass('template');

        // Prepare the value node.
        var count = field.find('fieldset.value').length;
        var namePrefix = field.data('property-term') + '[' + count + ']';
        value.find('input.property')
            .attr('name', namePrefix + '[property_id]')
            .val(valueObj['property_id']);
        $(document).trigger('o:prepare-value', [value, valueObj, type, namePrefix]);

        // Append and return.
        field.find('.values').append(value);
        return value;
    };

    $(document).on('o:prepare-value', function(e, value, valueObj, type, namePrefix) {
        if (!valueObj) {
            return;
        }
        if (type === 'literal') {
            value.find('textarea.input-value')
                .attr('name', namePrefix + '[@value]')
                .val(valueObj['@value']);
            value.find('input.value-language')
                .attr('name', namePrefix + '[@language]')
                .val(valueObj['@language']);
        } else if (type === 'uri') {
            value.find('input.value')
                .attr('name', namePrefix + '[@id]')
                .val(valueObj['@id']);
            value.find('textarea.value-label')
                .attr('name', namePrefix + '[o:uri_label]')
                .val(valueObj['o:uri_label']);
        } else if (type === 'resource') {
            
        }
    });

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

    var rewritePropertyFromTemplateProperty = function(template, index, templates) {
        var propertiesContainer = $('div#properties');
        var id = template['o:property']['o:id'];
        var field = propertiesContainer.find('fieldset[data-property-id="' + id + '"]');
        if (field.length == 0) {
            field = makeNewField(id);
            var qName = field.data('property-term');
            $('fieldset.resource-values.field[data-property-term="' + qName + '"] .values').append(makeNewValue(qName));
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
        propertiesContainer.prepend(field);
    };

    /**
     * Figure out if a valueObject is a literal, internal resource, or external resource
     */
    var valueObjectType = function(valueObject) {
        if (typeof valueObject['@value'] === 'string') {
            return 'literal';
        } else {
            if (typeof valueObject['value_resource_id'] === 'undefined') {
                return 'uri';
            } else {
                return 'resource';
            }
        }
    };

    var initPage = function() {
        if (typeof valuesJson == 'undefined') {
            makeNewField('dcterms:title');
            makeNewField('dcterms:description');
        } else {
            $.each(valuesJson, function(term, valueObj) {
                var field = makeNewField(term);
                $.each(valueObj.values, function(index, value) {
                    makeNewValue(field, value);
                });
            });
        }

        //rewrite the fields if a template is set
        var templateSelect = $('#resource-template-select');
        var templateId = templateSelect.val();
        if ($.isNumeric(templateId)) {
            var url = templateSelect.data('api-base-url') + '/' + templateId;
            $.ajax({
                'url': url,
                'type': 'get'
            }).done(function(data) {
                //reverse the templates because the need to be prepended
                var propertyTemplates = data['o:resource_template_property'].reverse(); 
                propertyTemplates.forEach(rewritePropertyFromTemplateProperty);
            }).error(function() {
                console.log('fail');
            });
        }

        $('input.value-language').each(function() {
            var languageInput = $(this);
            if (languageInput.val() !== "") {
                languageInput.addClass('active');
                languageInput.prev('a.value-language').addClass('active');
            }
        });
    };
})(jQuery);


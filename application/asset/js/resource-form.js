(function($) {

    $(document).ready( function() {
        // Select property
        $('#property-selector li.selector-child').on('click', function(e) {
            e.stopPropagation();
            var propertyLi = $(this);
            var qName = propertyLi.data('property-term');
            var propertyField = $('[data-property-term = "' + qName + '"].field');
            var count = propertyField.length;
            // If property has already been set, scroll to its field
            if (count > 0) {
                scrollTo(propertyField.first());
            } else {
                makeNewField(propertyLi);
                var wrapper = $('fieldset.resource-values.field[data-property-term="' + qName + '"] .values');
                wrapper.append(makeNewValue(qName));
                scrollTo(wrapper);
            }
        });

        //handle changing the resource template
        $('#resource-template-select').on('change', function(e) {
            var templateId = $(this).val();
            $('.alternate').remove();
            $('.field-label, .field-description').show();
            var url = $(this).data('api-base-url') + templateId;
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

        // Make resource public or private
        $('a.o-icon-private, a.o-icon-public').click(function(e) {
            e.preventDefault();
            var isPublicIcon = $(this);
            $(this).toggleClass('o-icon-private').toggleClass('o-icon-public');
            var isPublicHiddenValue = $('input[name="o:is_public"]');
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

        $('.sidebar').on('click', 'div.resource-list a.sidebar-content', function() {
            var resourceId = $(this).data('resource-id');
            $('#select-item a').data('resource-id', resourceId);
            });

        $('.sidebar').on('click', '.pagination a', function(e) {
            e.preventDefault();
            var sidebarContent = $(this).parents('div.sidebar-content');
            $.ajax({
                'url': $(this).attr('href'),
                'type': 'get'
            }).done(function(data) {
                sidebarContent.html(data);
                $(document).trigger('o:sidebar-content-loaded');
            }).error(function() {
                sidebarContent.html("<p>Something went wrong</p>");
            });
        });

        $('.sidebar').on('click', '#sidebar-resource-search .o-icon-search', function() {
            var searchValue = $('#resource-list-search').val();
            var sidebarContent = $(this).parents('div.sidebar-content');
            $.ajax({
                'url': $(this).data('search-url'),
                'data': {'value[in][]': searchValue},
                'type': 'get'
            }).done(function(data) {
                sidebarContent.html(data);
                $(document).trigger('o:sidebar-content-loaded');
            }).error(function() {
                sidebarContent.html("<p>Something went wrong</p>");
            });
        });

        $('.sidebar .sidebar').on('click', '#select-item a', function(e) {
            e.preventDefault();
            var propertyQname = $(this).data('property-term');
            var valuesData = $('.resource-details').data('resource-values');
            $('.value.selecting-resource').replaceWith(makeNewValue(propertyQname, valuesData));
            Omeka.closeSidebar($('.sidebar .sidebar'));
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

        $('.resource-name a').on('click', function(e) {
            e.preventDefault();
            var context = $(this);
            $('#resource-details').data('resource-id', $(this).data('resource-id'));
            $.ajax({
                'url': context.data('show-details-action'),
                'data': {'link-title' : 0},
                'type': 'get'
            }).done(function(data) {
                $('#resource-details-content').html(data);
                $('#select-item a').data('resource-id', context.data('resource-id'));
            });
            Omeka.openSidebar(context);
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

    var makeNewValue = function(qName, valueObject, valueType) {
        var valuesWrapper = $('fieldset.resource-values.field[data-property-term="' + qName + '"]');
        var count = valuesWrapper.find('input.property').length;
        var propertyId = valuesWrapper.data('property-id');
        var languageElementName = qName + '[' + count + '][@language]';
        if (typeof valueObject != 'undefined' && typeof valueType === 'undefined') {
            valueType = valueObjectType(valueObject);
        }

        var newValue = $('.value.template.' + valueType).clone(true);
        newValue.removeClass('template');
        newValue.data('base-name', qName + '[' + count + ']');

        var propertyInput = newValue.find('input.property');
        propertyInput.attr('name', qName + '[' + count + '][property_id]');

        if (typeof valueObject != 'undefined' && typeof valueObject['property_id'] != 'undefined') {
            propertyInput.val(valueObject['property_id']);
        } else {
            propertyInput.val(propertyId);
        }
        
        // set up text inputs
        var valueTextarea = newValue.find('textarea');
        var languageLabel = newValue.find('label.value-language');
        var languageInput = newValue.find('input.value-language');
        valueTextarea.attr('name', qName + '[' + count + '][@value]');
        languageLabel.attr('for', languageElementName); // 
        languageInput.attr('name', languageElementName).attr('id', languageElementName);

        //set up uri inputs
        var uriInput = newValue.find('input.value');
        var uriInputLabel = newValue.find('label.value');
        var uriInputId = qName + '[' + count + '][@id]';
        var uriLabelId = qName + '[' + count + '][o:uri_label]'
        uriInput.attr('name', uriInputId).attr('id', uriInputId);
        uriInputLabel.attr('for', uriInputId);
        var uriLabel = newValue.find('textarea.value-label');
        var uriLabelLabel = newValue.find('label.value-label');
        uriLabel.attr('name', uriLabelId).attr('id', uriLabelId);
        uriLabelLabel.attr('for', uriLabelId);

        var showRemoveValue = false;
        if (typeof valueObject !== 'undefined') {
            showRemoveValue = true;

            switch (valueType) {
                case 'literal' :
                    valueTextarea.val(valueObject['@value']);
                    languageInput.val(valueObject['@language']);
                break;

                case 'resource' :
                    var valueInternalInput = newValue.find('input.value');
                    var newResource = newValue.find('.selected-resource');
                    var title = valueObject['display_title'];
                    if (typeof valueObject['value_resource_id'] === 'undefined') {
                        break;
                    }

                    newResource.prev('.default').hide();
                    if (typeof title == 'undefined') {
                        title = '[Untitled]';
                    }
                    var link = $('<a></a>', {
                        href: valueObject['url'],
                        text: title
                    });

                    newResource.find('.o-title').append(link).addClass(valueObject['value_resource_name']);

                    valueInternalInput.attr('name', qName + '[' + count + '][value_resource_id]');
                    valueInternalInput.val(valueObject['value_resource_id']);
                break;

                case 'uri' :
                    uriInput.val(valueObject['@id']);
                    uriLabel.val(valueObject['o:uri_label']);
                break;
            }
        }

        return newValue; 
    };

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

        var qName = propertyLi.data('property-term');
        var field = $('.resource-values.field.template').clone(true);
        field.removeClass('template');
        var fieldName = propertyLi.data('child-label');
        field.find('.field-label').text(fieldName);
        field.find('.field-term').text(qName);
        var fieldDesc = $('.description p', propertyLi).last();
        field.find('.field-description').prepend(fieldDesc.text());
        $('div#properties').append(field);
        field.data('property-term', qName);
        field.data('property-id', propertyId);
        //adding the att because selectors need them to find the correct field and count when adding more
        //should I put a class with the 
        field.attr('data-property-term', qName);
        field.attr('data-property-id', propertyId);
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
        if (template['o:alternate_label'] != "") {
            var altLabel = originalLabel.clone();
            altLabel.addClass('alternate');
            altLabel.text(template['o:alternate_label']);
            altLabel.insertAfter(originalLabel);
            originalLabel.hide();
        }

        var originalDescription = field.find('.field-description');
        if (template['o:alternate_comment'] != "") {
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
        if (typeof valueObject['@value'] == 'string') {
            return 'literal';
        } else {
            if (typeof valueObject['value_resource_id'] == 'undefined') {
                return 'uri';
            } else {
                return 'resource';
            }
        }
    };

    var initPage = function() {
        //clone dcterms:title and dcterms:description for starters, if they don't already exist
        //assumes that the propertySelector helper has been deployed

        if (typeof valuesJson == 'undefined') {
            makeNewField('dcterms:title');
            makeNewField('dcterms:description');
        } else {
            for (var term in valuesJson) {
                makeNewField(term);
                valuesJson[term].values.forEach(function (value) {
                    $('fieldset.resource-values.field[data-property-term="' + term + '"] .values').append(makeNewValue(term, value));
                });
            }
        }

        //rewrite the fields if a template is set
        var templateSelect = $('#resource-template-select');
        var templateId = templateSelect.val();
        if ($.isNumeric(templateId)) {
            var url = templateSelect.data('api-base-url') + templateId;
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
    };

    var scrollTo = function(wrapper) {
        //focus on the value being edited
        $('html, body').animate({
            scrollTop: (wrapper.offset().top -100)
        },200);
    }
})(jQuery);


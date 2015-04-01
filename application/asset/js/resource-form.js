(function($) {

    $(document).ready( function() {
        // Select property
        $('li.property').on('click', function(e) {
            e.stopPropagation();
            var propertyLi = $(this);
            var qName = propertyLi.data('property-term');
            var propertyField = $('[data-property-term = "' + qName + '"].field');
            var count = propertyField.length;
            // If property has already been set, scroll to its field
            if (count > 0) {
                $('html, body').animate({
                    scrollTop: (propertyField.first().offset().top -100)
                },200);
            } else {
                makeNewField(propertyLi);
                makeNewValue(qName, true);
            }
        });

        //handle changing the resource template
        $('#resource-template-select').on('change', function(e) {
            var templateId = $(this).find(':selected').val();
            if (templateId == "") {
                $('#properties').empty();
                initPage();
            } else {
                var url = $(this).data('api-base-url') + templateId;
                $.ajax({
                    'url': url,
                    'type': 'get'
                }).done(function(data) {
                    if (data['o:resource_class']) {
                        $('select#resource-class-select').val(data['o:resource_class']['o:id']);
                    } else {
                        $('select#resource-class-select').val("");
                    }
                    //in case people have added fields, reverse the template so
                    //I can prepend everything and keep the order, and then drop
                    //back to what people have added
                    var propertyTemplates = data['o:resource_template_property'].reverse(); 
                    propertyTemplates.forEach(rewritePropertyFromTemplateProperty);
                }).error(function() {
                    console.log('fail');
                });
            }
        });

        // Make new value inputs whenever "add value" button clicked.
        $('.add-value').on('click', function(e) {
            e.preventDefault();
            var wrapper = $(this).parents('.resource-values.field');
            var type = $(this).attr('class').replace(/o-icon-/, '').replace(/button/, '').replace(/add-value/, '').replace(/ /g, '');
            makeNewValue(wrapper.data('property-term'), false, false, type);
        });

        // Remove value.
        $('a.remove-value').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var valueToRemove = $(this).parents('.value');
            //check if there is an value_id, which indicates the value has
            //already been saved
            if (valueToRemove.find('input.value-id').length == 0 ) {
                valueToRemove.find('input').prop('disabled', true);
                valueToRemove.find('textarea').prop('disabled', true);
            } else {
                var deleteInput = $('<input>').addClass('delete').attr('type', 'hidden').val(1);
                deleteInput.attr('name', valueToRemove.data('base-name') + '[delete]');
                valueToRemove.append(deleteInput);
            }
            valueToRemove.addClass('delete');
            valueToRemove.find('a.restore-value').show().focus();
            $(this).hide();
        });

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
            //@TODO: right now this is creating a new value, and deleting the value
            //that was clicked. Seems like steps can be removed in the workflow?
            $('.value.selecting-resource').remove();
            var valuesData = $('.resource-details').data('resource-values');
            makeNewValue(propertyQname, false, valuesData);
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
            Omeka.openSidebar(context);
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

        $('.field-meta .expand, .field-meta .collapse').each(function(e) {
            var nextSiblings = $(this).nextAll();
            nextSiblings.wrapAll('<div class="collapsible"></div>');
        });

        initPage();
    });

    var makeNewValue = function(qName, focus, valueObject, valueType) {
        var valuesWrapper = $('div.resource-values.field[data-property-term="' + qName + '"]');
        var count = valuesWrapper.find('input.property').length;
        var propertyId = valuesWrapper.data('property-id');
        var languageElementName = qName + '[' + count + '][@language]';
        if (typeof valueObject != 'undefined' && typeof valueType === 'undefined') {
            valueType = valueObjectType(valueObject);
        }

        var newValue = $('.value.template.' + valueType).clone(true);
        newValue.removeClass('template');
        newValue.data('base-name', qName + '[' + count + ']');
        valuesWrapper.find('.values').append(newValue);

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
        languageInput.attr('name', languageElementName);

        //set up uri inputs
        var uriInput = newValue.find('input.value');
        uriInput.attr('name', qName + '[' + count + '][@id]');
        
        var valueIdInput = newValue.find('input.value-id');

        var showRemoveValue = false;
        if (typeof valueObject == 'undefined') {
            valueIdInput.remove();
        } else {
            showRemoveValue = true;
            valueIdInput.attr('name', qName + '[' + count + '][value_id]');
            if (valueObject['value_id']) {
                valueIdInput.val(valueObject['value_id']);
            } else {
                valueIdInput.remove();
            }
            
            switch (valueType) {
                case 'literal' :
                    valueTextarea.val(valueObject['@value']);
                    languageInput.val(valueObject['@language']);
                break;

                case 'resource' :
                    var valueInternalInput = newValue.find('input.value');
                    var newResource = newValue.find('.selected-resource');
                    var title = valueObject['dcterms:title'];
                    if (typeof valueObject['value_resource_id'] === 'undefined') {
                        break;
                    }

                    newResource.prev('.default').hide();
                    if (typeof title == 'undefined') {
                        title = '[Untitled]';
                    }
                    var html = "<a href='" + valueObject['url'] + "'>" + valueObject['dcterms:title'] + "</a>";
                    newResource.find('.o-title').html(html);

                    valueInternalInput.attr('name', qName + '[' + count + '][value_resource_id]');
                    valueInternalInput.val(valueObject['value_resource_id']);

                break;

                case 'uri' :
                    uriInput.val(valueObject['@id']);
                break;
            }
        }
        if (focus) {
            $('html, body').animate({
                scrollTop: (valuesWrapper.offset().top -100)
            },200);
            $('textarea', newValue).focus();
        } 
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
                propertyLi = $('.property-selector').find("li[data-property-id='" + propertyId + "']");
            break;

            case 'string':
                propertyLi = $('.property-selector').find("li[data-property-term='" + property + "']");
                propertyId = propertyLi.data('property-id');
            break;
        }

        var qName = propertyLi.data('property-term');
        var field = $('.resource-values.field.template').clone(true);
        field.removeClass('template');
        var fieldName = $('span.property-label', propertyLi).html();
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
        var field = propertiesContainer.find('div[data-property-id="' + id + '"]');
        if (field.length == 0) {
            field = makeNewField(id);
            makeNewValue(field.data('property-term'));
        }

        if (template['o:alternate_label'] == "" || template['o:alternate_comment'] == "") {
            var propertyLi = $('.property-selector').find("li[data-property-id='" + id + "']");
        }

        if (template['o:alternate_label'] == "") {
            var label = propertyLi.find('span.property-label');
            field.find('span.field-label-text').text(label.text());
        } else {
            field.find('span.field-label-text').text(template['o:alternate_label']);
        }

        if (template['o:alternate_comment'] == "") {
            var description = propertyLi.find('.description p').last();
            field.find('p.field-comment').text(description.text());
        } else {
            field.find('p.field-comment').text(template['o:alternate_comment']);
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
                for (var i=0; i < valuesJson[term].length; i++) {
                    makeNewValue(term, false, valuesJson[term][i]);
                }
            }
        }

        //rewrite the fields if a template is set
        var templateSelect = $('#resource-template-select');
        var templateId = templateSelect.find(':selected').val();
        if (templateId != "") {
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
})(jQuery);


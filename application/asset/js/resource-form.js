(function($) {

    $(document).ready( function() {
        // Mark existing properties for deletion and straight up remove new properties.
        $('.remove.button').on('click', function(e) {
            e.preventDefault();
            var currentField = $(this).parents('.field');
            if (currentField.hasClass('new')) {
                currentField.remove();
            } else {
                currentField.toggleClass('remove');
            }
        });

        /* Property selector handlers */

        // Select property
        $('li.property').on('click', function(e) {
            e.stopPropagation();
            var propertyLi = $(this);
            var qName = propertyLi.data('property-term');
            var count = $('input.input-id[data-property-term = "' + qName + '"]').length;
            // If property has already been set, add a new value
            if (count > 0) {
                makeNewValue(qName, true);
            } else {
                makeNewField(propertyLi);
                makeNewValue(qName, true);
            }
        });

        /* End Property Selector Handlers */

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
            makeNewValue(wrapper.data('property-term'));
        });

        // Remove value.
        $('a.remove-value').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var valueToRemove = $(this).parents('.value');
            var parentInput = $(this).parents('.inputs');
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
            valueToRemove.find('a.restore-value').show();
            $(this).hide();
        });

        $('a.restore-value').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var valueToRemove = $(this).parents('.value');
            valueToRemove.find('a.remove-value').show();
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
            var sidebarContent = $('#sidebar .sidebar-content');
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
            var sidebarContent = $('#sidebar .sidebar-content');
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
            var valuesData = $('.item-details').data('item-values');
            makeNewValue(propertyQname, false, valuesData);
            Omeka.closeSidebar($('.sidebar'));
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

        // Keep new fields that have been changed.
        $(document).on('change', '.items .field input', function() {
            $(this).parents('.field').addClass('keep');
        });

        initPage();
    });

    var makeNewValue = function(qName, focus, valueObject) {
        var valuesWrapper = $('div.resource-values.field[data-property-term="' + qName + '"]');
        var newValue = $('.value.template ').clone(true);
        newValue.removeClass('template');
        var count = valuesWrapper.find('input.value').length;
        newValue.data('base-name', qName + '[' + count + ']');
        valuesWrapper.find('.inputs').append(newValue);
        var propertyId = valuesWrapper.data('property-id');
        var languageElementName = qName + '[' + count + '][@language]';

        var propertyIdInput = newValue.find('.input-id');
        var valueTextarea = newValue.find('textarea');
        var languageLabel = newValue.find('label.value-language');
        var languageInput = newValue.find('input.value-language');
        propertyIdInput.val(propertyId);
        propertyIdInput.attr('name', qName + '[' + count + '][property_id]');
        propertyIdInput.attr('data-property-term', qName);
        valueTextarea.attr('name', qName + '[' + count + '][@value]');
        languageLabel.attr('for', languageElementName);
        languageInput.attr('name', languageElementName);

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

            var type = valueObjectType(valueObject);
            languageInput.val(valueObject['@language']);
            switch (type) {
                case 'literal' :
                    valueTextarea.val(valueObject['@value']);
                break;

                case 'resource' :
                    valueTextarea.remove();
                    var valueInternalInput = newValue.find('input.value');
                    var newResource = newValue.find('.selected-resource');
                    newResource.removeClass('template');
                    if (typeof valueObject['dcterms:title'] == 'undefined') {
                        newResource.find('.o-title').html('[Untitled]');
                    } else {
                        var html = "<a href='" + valueObject['url'] + "'>" + valueObject['dcterms:title'] + "</a>";
                        newResource.find('.o-title').html(html);
                    }
                    valueInternalInput.attr('name', qName + '[' + count + '][value_resource_id]');
                    valueInternalInput.val(valueObject['value_resource_id']);
                    var propertyInput = newValue.find('input.property');
                    propertyInput.attr('name', qName + '[' + count + '][property_id]');
                    if (valueObject['property_id']) {
                        propertyInput.val(valueObject['property_id']);
                    } else {
                        propertyInput.val(propertyId);
                    }

                    //set up the buttons for actions

                    newResource.siblings('span').hide();
                    newResource.siblings('a.button').hide();
                    newResource.parent().siblings('button.remove-value').addClass('active');

                    var activeTab = newValue.find('.o-icon-items');
                    Omeka.switchValueTabs(activeTab);
                break;

                case 'external' :
                    var activeTab = newValue.find('.o-icon-link');
                    Omeka.switchValueTabs(activeTab);
                break;
            }
        }
        if (focus) {
            $('html, body').animate({
                scrollTop: (valuesWrapper.offset().top -100)
            },200);
            $('textarea', newValue).focus();
        } 

        //decide whether to show the 'remove value' trashcan base on number of values
        var removeValueButton = valuesWrapper.find('a.remove-value');
        if (count > 0) {
            showRemoveValue = true;
        }

        if (showRemoveValue) {
            removeValueButton.show();
        } else {
            removeValueButton.hide();
        }

        
        // elements are counted before the newest is added
        /*
        if (count > 0) {
            valuesWrapper.find('.remove-value').addClass('active');
        }
        */
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
        field.find('span.field-label-text').text(fieldName);
        field.find('span.field-term').text(qName);
        var fieldDesc = $('.description p', propertyLi).last();
        field.find('.field-comment').text(fieldDesc.text());
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
            if (typeof valueObject['resource_id'] == 'undefined') {
                return 'resource';
            } else {
                return 'external';
            }
        }
    };
    
    var initPage = function() {
        //clone dcterms:title and dcterms:description for starters, if they don't already exist
        //assumes that the propertySelector helper has been deployed
        
        if (typeof valuesJson == 'undefined') {
            makeNewField('dcterms:title');
            makeNewValue('dcterms:title');
            makeNewField('dcterms:description');
            makeNewValue('dcterms:description');
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


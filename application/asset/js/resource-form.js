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
        
        //clone dcterms:title and dcterms:description for starters, if they don't already exist
        //assumes that the propertySelector helper has been deployed
        
        var titleLi = $('li[data-property-term="dcterms:title"]');
        var qName = titleLi.data('property-term');
        makeNewField(titleLi);
        if (typeof valuesJson != 'undefined' && typeof valuesJson['dcterms:title'] != 'undefined') {
            for (var i=0; i < valuesJson['dcterms:title'].length; i++) {
                makeNewValue(qName, true, valuesJson['dcterms:title'][i]);
            }
        } else {
            makeNewValue(qName, true);
        }
        

        var descriptionLi = $('li[data-property-term="dcterms:description"]');
        var qName = descriptionLi.data('property-term');
        makeNewField(descriptionLi);
        if (typeof valuesJson != 'undefined' && typeof valuesJson['dcterms:description'] != 'undefined') {
            for (var i=0; i < valuesJson['dcterms:description'].length; i++) {
                makeNewValue(qName, true, valuesJson['dcterms:description'][i]);
            }
        } else {
            makeNewValue(qName, true);
        }
        
        //rewrite the fields if a template has been selected when editing
        
        var templateSelect = $('#resource-template-select'); 
        var templateId = templateSelect.find(':selected').val();
        if (templateId != "") {
            var url = templateSelect.data('api-base-url') + templateId;
            $.ajax({
                'url': url,
                'type': 'get'
            }).done(function(data) {
                if (data['o:resource_class']) {
                    console.log(data['o:resource_class']['o:id']);
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
        /* Property selector handlers */

        // Select property
        $('li.property').on('click', function(e) {
            e.stopPropagation();
            var propertyLi = $(this);
            var qName = propertyLi.data('property-term');
            var count = $('input.input-id[data-property-term = "' + qName + '"]').length;
            // If property has already been set, add a new value
            if (count > 0) {
                makeNewValue(qName);
            } else {
                makeNewField(propertyLi);
                makeNewValue(qName);
            }
        });

        /* End Property Selector Handlers */
        
        //handle changing the resource template
        
        templateSelect.on('change', function(e) {
            var templateId = $(this).find(':selected').val();
            if (templateId != "") {
                var url = $(this).data('api-base-url') + templateId;
                $.ajax({
                    'url': url,
                    'type': 'get'
                }).done(function(data) {
                    if (data['o:resource_class']) {
                        console.log(data['o:resource_class']['o:id']);
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
        $('.remove-value').on('click', function(e) {
            e.preventDefault();
            var valueToRemove = $(this).parents('.value');
            var parentInput = $(this).parents('.inputs');
            //check if there is an value_id, which indicates the value has
            //already been saved
            if (valueToRemove.find('input.value-id').val() == '' ) {
                valueToRemove.remove();
            } else {
                var deleteInput = $('<input>').addClass('delete').attr('type', 'hidden').val(1);
                deleteInput.attr('name', valueToRemove.data('base-name') + '[delete]');
                valueToRemove.append(deleteInput);
                //@TODO: maybe handle all this with a class? Q for Kim.
                valueToRemove.attr('style', "background-color: #ffcccc;");
                valueToRemove.find('input').attr('style', "background-color: #ffcccc;");
                valueToRemove.find('textarea').attr('style', "background-color: #ffcccc;");
                valueToRemove.addClass('delete');
            }
            var count = parentInput.find('> .value').length;
            if (count == 1) {
                parentInput.find('.remove-value').removeClass('active');
                makeNewValue();
            }
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
            var title = $('#resource-details .o-title').html();
            var resourceId = $(this).data('resource-id');
            var propertyQname = $(this).data('property-term');
            var valuesWrapper = $('div.resource-values.field[data-property-term="' + propertyQname + '"]');
            var count = valuesWrapper.find('input.value').length;
            var newResource = valuesWrapper.find("p.selected-resource.template");
            newResource.removeClass('template');
            newResource.find('span.o-title').html(title);
            
            //instead of what's currently here, completely replace the existing
            //inputs for value and property, for the "value" box being used
            
            var valueInput = newResource.find('input.value'); 
            valueInput.attr('name', propertyQname + '[' + count + '][value_resource_id]');
            valueInput.val(resourceId);
            var propertyInput = $('input.property', newResource);
            propertyInput.attr('name', propertyQname + '[' + count + '][property_id]');
            propertyInput.val(valuesWrapper.data('property-id'));
            
            //set up the buttons for actions
            newResource.siblings('span').hide();
            newResource.siblings('a.button').hide();
            newResource.parent().siblings('button.remove-value').addClass('active');

            Omeka.closeSidebar($('.sidebar'));
        });

        $('.button.resource-select').on('click', function(e) {
            e.preventDefault();
            var context = $(this);
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
    });

    var makeNewValue = function(qName, skipFocus, valueObject) {
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
        propertyIdInput.val(propertyId).attr('name', qName + '[' + count + '][property_id]');
        propertyIdInput.attr('data-property-term', qName);
        valueTextarea.attr('name', qName + '[' + count + '][@value]');
        languageLabel.attr('for', languageElementName);
        languageInput.attr('name', languageElementName);

        if (typeof valueObject != 'undefined') {
            var valueIdInput = newValue.find('input.value-id');
            valueIdInput.attr('name', qName + '[' + count + '][value_id]');
            valueIdInput.val(valueObject['value_id']);
            var type = valueObjectType(valueObject);
            languageInput.val(valueObject['@language']);
            switch (type) {
                case 'literal' :
                    valueTextarea.val(valueObject['@value']);
                break;
                
                case 'resource' :
                    var valueInternalInput = newValue.find('input.value');
                    var newResource = newValue.find('.selected-resource');
                    newResource.removeClass('template');
                    if (typeof valueObject['dcterms:title'] == 'undefined') {
                        //@TODO: figure out how to translate this
                        newResource.find('.o-title').html('[Untitled]');
                    } else {
                        var html = "<a href='" + valueObject['url'] + "'>" + valueObject['dcterms:title'] + "</a>";
                        newResource.find('.o-title').html(html);
                    }
                    valueInternalInput.attr('name', qName + '[' + count + '][value_resource_id]');
                    valueInternalInput.val(valueObject['value_resource_id']);
                    var propertyInput = newValue.find('input.property');
                    propertyInput.attr('name', qName + '[' + count + '][property_id');
                    propertyInput.val(valueObject['property_id']);
                    
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

        // the skipFocus was added late in dev. should be refactored to make more sense
        // and use 'focus' as the variable
        if (typeof skipFocus == 'undefined') {
            $('html, body').animate({
                scrollTop: (valuesWrapper.offset().top -100)
            },200);
            $('textarea', newValue).focus();
        } 

        // elements are counted before the newest is added
        if (count > 0) {
            valuesWrapper.find('.remove-value').addClass('active');
        }
    };
    
    var makeNewField = function(propertyLi) {
        var qName = propertyLi.data('property-term');
        var propertyId = propertyLi.data('property-id');
        var field = $('.resource-values.field.template').clone(true);
        field.removeClass('template');
        var fieldName = $('span.property-label', propertyLi).html() + ' (' + Omeka.cleanText(propertyLi.parents('.vocabulary').find('.vocabulary-name')) + ')';
        $('label', field).text(fieldName);
        var fieldDesc = $('.description p', propertyLi).last();
        field.find('.field-comment').text(fieldDesc.text());
        $('div#properties').append(field);
        field.data('property-term', qName);
        field.data('property-id', propertyId);
        //adding the att because selectors need them to find the correct field and count when adding more
        //should I put a class with the 
        field.attr('data-property-term', qName);
        field.attr('data-property-id', propertyId);
    };

    var rewritePropertyFromTemplateProperty = function(template, index, templates) {
        var propertiesContainer = $('div#properties');
        var id = template['o:property']['o:id'];
        var field = propertiesContainer.find('div[data-property-id="' + id + '"]');
        if (template['o:alternate_label'] != "") {
            field.find('label.field-label').text(template['o:alternate_label']);
        }
        
        if (template['o:alternate_comment'] != "") {
            field.find('field-comment').text(template['o:alternate_comment']);
        } 
        
        if (field.length == 0) {
            //field = makeNewField() 
            //refactor makeNewField
            //pass in either term or property id. decide based on what's more available???
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
})(jQuery);


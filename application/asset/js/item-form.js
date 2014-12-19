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
        $('.select-property-button').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var propertyLi = $(this).closest('.property');
            var qName = propertyLi.data('property-qname');
            var count = $('input.input-id[data-property-qname = "' + qName + '"]').length;
            // If property has already been set, add a new value
            if (count > 0) {
                makeNewValue(qName);
            } else {
                makeNewField(propertyLi);
                makeNewValue(qName);
            }
        });

        /* End Property Selector Handlers */
        
        // Make new value inputs whenever "add value" button clicked.
        $('.add-value').on('click', function(e) {
            e.preventDefault();
            var wrapper = $(this).parents('.resource-values.field');
            makeNewValue(wrapper.data('property-qname'));
        });

        // Remove value.
        $('.remove-value').on('click', function(e) {
            e.preventDefault();
            var valueToRemove = $(this).parents('.value');
            var parentInput = $(this).parents('.inputs');
            valueToRemove.remove();
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
            var propertyQname = $(this).data('property-qname');
            var valuesWrapper = $('div.resource-values.field[data-property-qname="' + propertyQname + '"]');
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
            var qName = context.parents('.resource-values').data('property-qname');
            $('#select-item a').data('property-qname', qName);
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
                console.log(context.data('resource-id'));
                $('#select-item a').data('resource-id', context.data('resource-id'));
                
            });
            Omeka.openSidebar(context);
        });

        // Keep new fields that have been changed.
        $(document).on('change', '.items .field input', function() {
            $(this).parents('.field').addClass('keep');
        });
    });

    var makeNewValue = function(qName) {
        var valuesWrapper = $('div.resource-values.field[data-property-qname="' + qName + '"]');
        var newValue = $('.value.template ').clone(true);
        newValue.removeClass('template');
        var count = valuesWrapper.find('input.value').length;
        $('.inputs', valuesWrapper).append(newValue);
        var propertyId = valuesWrapper.data('property-id');
        var languageElementName = qName + '[' + count + '][@language]';
        $('.input-id', newValue).val(propertyId).attr('name', qName + '[' + count + '][property_id]');
        $('.input-id', newValue).attr('data-property-qname', qName);
        $('textarea', newValue).attr('name', qName + '[' + count + '][@value]');
        $('label.value-language', newValue).attr('for', languageElementName);
        $('input.value-language', newValue).attr('name', languageElementName);
        
        $('html, body').animate({
            scrollTop: (valuesWrapper.offset().top -100)
        },200);
        $('textarea', newValue).focus();
        // elements are counted before the newest is added
        if (count > 0) {
            valuesWrapper.find('.remove-value').addClass('active');
        }
    };
    
    var makeNewField = function(propertyLi) {
        var qName = propertyLi.data('property-qname');
        var propertyId = propertyLi.data('property-id');
        var field = $('.resource-values.field.template').clone();
        field.removeClass('template');
        var fieldName = $('span.property-label', propertyLi).html() + ' (' + Omeka.cleanText(propertyLi.parents('.vocabulary')) + ')';
        $('label', field).text(fieldName);
        var fieldDesc = $('.description p', propertyLi).last();
        $('.field-description', field).append(fieldDesc);
        $('.new.resource-values.field').before(field);
        field.data('property-qname', qName);
        field.data('property-id', propertyId);
        //adding the att because selectors need them to find the correct field and count when adding more
        //should I put a class with the 
        field.attr('data-property-qname', qName);
        //field.attr('data-property-id', propertyId);
    };

})(jQuery);


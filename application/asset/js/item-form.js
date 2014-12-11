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

        // Show properties
        $('.property-selector li').on('click', function(e) {
            e.stopPropagation();
            if ($(this).children('li')) {
                $(this).toggleClass('show');
            }
        });

        // Select property
        $('.select-property').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var selectedProperty = $(this).parents('.properties').find('.selected').first();
            if (selectedProperty.length > 0) {
                selectedProperty.removeClass('selected');
            }
            $(this).parent().addClass('selected');
        });

        // Set property
        $('.set-property-button').on('click', function(e) {
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

        // Make new value inputs whenever "add value" button clicked.
        $('.add-value').on('click', function(e) {
            e.preventDefault();
            var wrapper = $(this).parents('.resource-values.field');
            makeNewValue(wrapper.data('property-qname'));
        });

        // Remove value.
        $('.remove-value').on('click', function(e) {
            e.preventDefault();
            var value = $(this).parents('.value');
            var count = $(this).parents('.field').find('.value').length;
            if (count > 1) {
                if (count == 2) {
                    $(this).parents('.field').find('.remove-value').last().removeClass('active');
                }
                value.remove();
            }
        });

        // Attach sidebar triggers

        $(document).bind('o:sidebar-content-loaded', function() {
            var sidebar = $('#content > .sidebar');
            Omeka.attachSidebarHandlers(sidebar);
            $('div.resource-list a.sidebar-content').on('click', function() {
                var resourceId = $(this).data('resource-id');
                $('#select-item a').data('resource-id', resourceId);
            });

            $('#sidebar-resource-search .o-icon-search').on('click', function() {
                var searchValue = $('#resource-list-search').val();
                console.log(searchValue);
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
        });

        $('#select-item a').click(function(e) {
            e.preventDefault();
            var title = $('#resource-details .o-title').html();
            var resourceId = $(this).data('resource-id');
            var propertyQname = $(this).data('property-qname');
            var valuesWrapper = $('div.resource-values.field[data-property-qname="' + propertyQname + '"]');
            var count = valuesWrapper.find('input.value').length;
            var ul = jQuery("div[data-property-qname = '" + propertyQname + "'] ul.selected-resources");
            var newResource = $("li.selected-resource.template", ul).clone();
            newResource.removeClass('template');
            newResource.find('span.o-title').html(title);
            var valueInput = $('input.value', newResource); 
            valueInput.attr('name', propertyQname + '[' + count + '][value_resource_id]');
            valueInput.val(resourceId);
            var propertyInput = $('input.property', newResource);
            propertyInput.attr('name', propertyQname + '[' + count + '][property_id]');
            propertyInput.val(valuesWrapper.data('property-id'));
            ul.append(newResource);
            if ($('li', ul).length == 1) {
                ul.siblings('span').show();
            } else {
                ul.siblings('span').hide();
            }
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
            });
            Omeka.openSidebar(context);
        });

        $('body.browse .fa-trash-o').click(function(e) {
            e.preventDefault();
            $.get('../common/delete-confirm.php', function(data) {
                $('.modal-content').html(data);
                $('.modal').attr('id', 'delete-confirm').attr('class', 'small modal');
                $('.modal-header h1').replaceWith($('.modal-content h1'));
            });
        });

        // Switch between the different value options.
        $(document).on('click', '.tab', function(e) {
            var tab = $(this);
            e.preventDefault();
            if (!$(this).hasClass('active')) {
                tab.siblings('.tab.active').removeClass('active');
                tab.parent().siblings('.active:not(.remove-value)').removeClass('active');
                var currentClass = '.' + tab.attr('class').split(" o-icon-")[1];
                tab.addClass('active');
                tab.parent().siblings(currentClass).addClass('active');
            }
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
        var fieldName = $('span.property-label', propertyLi).html() + ' (' + cleanText(propertyLi.parents('.vocabulary')) + ')';
        $('label', field).text(fieldName);
        var fieldDesc = $('.description p', propertyLi).last();
        $('.field-description', field).append(fieldDesc);
        $('.new.resource-values.field').before(field);
        field.data('property-qname', qName);
        field.data('property-id', propertyId);
        field.attr('data-property-qname', qName);
        field.attr('data-property-id', propertyId);
    };

    var cleanText = function(text) {
        newText = text.clone();
        newText.children().remove();
        newText = newText.text().replace(/^\s+|\s+$/g,'');
        return newText;
    };

})(jQuery);


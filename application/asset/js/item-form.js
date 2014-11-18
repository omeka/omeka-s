(function($) {

    $(document).ready( function() {

        // Skip to content button. See http://www.bignerdranch.com/blog/web-accessibility-skip-navigation-links/
        $('.skip').click(function(e) {
            $('#main').attr('tabindex', -1).on('blur focusout', function() {
                $(this).removeAttr('tabindex');
            }).focus();
        });

        // Mobile navigation
        $('#mobile-nav .button').click(function(e) {
            e.preventDefault();
            var buttonClass = $(this).attr('class');
            var navId = buttonClass.replace(/button/, '');
            var navObject = $('#' + navId.replace(/o-icon-/, ''));
            if ($('header .active').length > 0) {
                if (!($(this).hasClass('active'))) {
                    $('header .active').removeClass('active');
                    $(this).addClass('active');
                    navObject.addClass('active');
                } else {
                    $('header .active').removeClass('active');
                }
            } else {
                $(this).addClass('active');
                navObject.addClass('active');
            }
        });

        // Variables
        var addEditItems = $('body');

        // Switch between section tabs.
        $('a.section, .section legend').click(function(e) {
            e.preventDefault();
            var tab = $(this);
            if (!tab.hasClass('active')) {
                $('.section.active, legend.active').removeClass('active');
                if (tab.is('legend')) {
                    var sectionClass = tab.parents('.section').attr('id');
                } else {
                    var sectionClass = tab.attr('class');
                }
                var sectionId = sectionClass.replace(/section/, '');
                tab.addClass('active');
                $('#' + sectionId).addClass('active');
            }
        });

        // Set classes for expandable/collapsible content.
        $(document).on('click', 'a.expand, a.collapse', function(e) {
            e.preventDefault();
            $(this).toggleClass('collapse').toggleClass('expand');
            if ($('.expand-collapse-parent').length > 0) {
                $(this).parent().toggleClass('collapse').toggleClass('expand');
            }
        });

        // Show property descriptions when clicking "more-info" icon.
        addEditItems.on('click', '.property .o-icon-info', function() {
            $(this).parents('.description').toggleClass('show');
        });

        // Mark existing properties for deletion and straight up remove new properties.
        addEditItems.on('click', '.remove.button', function(e) {
            e.preventDefault();
            var currentField = $(this).parents('.field');
            if (currentField.hasClass('new')) {
                currentField.remove();
            } else {
                currentField.toggleClass('remove');
            }
        });

        // Show properties
        addEditItems.on('click', '.properties li', function(e) {
            e.stopPropagation();
            if ($(this).children('li')) {
                $(this).toggleClass('show');
            }
        });

        // Select property
        addEditItems.on('click', '.select-property', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var selectedProperty = $(this).parents('.properties').find('.selected').first();
            if (selectedProperty.length > 0) {
                selectedProperty.removeClass('selected');
            }
            $(this).parent().addClass('selected');
        });

        // Set property
        addEditItems.on('click', '.set-property', function(e) {
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
        addEditItems.on('click', '.add-value', function(e) {
            e.preventDefault();
            var wrapper = $(this).parents('.resource-values.field');
            makeNewValue(wrapper.data('property-qname'));
        });

        // Remove value.
        addEditItems.on('click', '.remove-value', function(e) {
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
        $('.o-icon-more').click(function(e) {
            e.preventDefault();
            openSidebar($('.sidebar'));
            $('#delete').hide();
            $('#more').show();
        });

        $('.o-icon-delete').click(function(e) {
            e.preventDefault();
            openSidebar($('.sidebar'));
            $('#more').hide();
            $('#delete').show();
        });

        $('.sidebar-close').click(function(e) {
            e.preventDefault();
            $(this).parent('.active').removeClass('active');
            if ($('.active.sidebar').length < 1) {
                $('#content').removeClass('sidebar-open');
            }
        });

        $('#select-item a').click(function(e) {
            e.preventDefault();
            selectResource();
        });

        if ($('body').hasClass('add')) {
            $('body').on('click','[href="#resource-select"]', function(e) {
                e.preventDefault();
                var qName = $(this).parents('.resource-values').data('property-qname');
                $('#resource-details').data('property-qname', qName);
                openSidebar($('#content > .sidebar'));
            });
            $('body').on('click','.resource-name a', function(e) {
                e.preventDefault();
                $('#resource-details').data('resource-id', $(this).data('resource-id'));
                $.ajax({
                    'url': $(this).data('show-details-action'),
                    'data': {'link-title' : 0},
                    'type': 'get'
                }).done(function(data) {
                    $('#resource-details-content').html(data);
                });
                openSidebar($('.sidebar .sidebar'));
            });
        }

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
        var newValue = $('.value.template ').clone();
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

    var selectResource = function() {
        var resource = $('#resource-details');
        var propertyQname = resource.data('property-qname');
        var valuesWrapper = $('div.resource-values.field[data-property-qname="' + propertyQname + '"]');
        var count = valuesWrapper.find('input.value').length;
        var ul = jQuery("div[data-property-qname = '" + propertyQname + "'] ul.selected-resources");
        var newResource = $("li.selected-resource.template", ul).clone();
        newResource.removeClass('template');
        $('span', newResource).html($('.o-title', resource).html());
        var valueInput = $('input.value', newResource); 
        valueInput.attr('name', propertyQname + '[' + count + '][value_resource_id]');
        valueInput.val(resource.data('resource-id'));
        var propertyInput = $('input.property', newResource);
        propertyInput.attr('name', propertyQname + '[' + count + '][property_id]');
        propertyInput.val(valuesWrapper.data('property-id'));
        ul.append(newResource);
        if ($('li', ul).length == 1) {
            ul.siblings('span').show();
        } else {
            ul.siblings('span').hide();
        }
    };

    var openSidebar = function(element) {
        element.addClass('active');
        if (!$('#content').hasClass('sidebar-open')) {
            $('#content').addClass('sidebar-open');
        }
    };

    var cleanText = function(text) {
        newText = text.clone();
        newText.children().remove();
        newText = newText.text().replace(/^\s+|\s+$/g,'');
        return newText;
    };

})(jQuery);


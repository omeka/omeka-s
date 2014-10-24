(function($) {

    $(document).ready( function() {
        //have an initial property input available to use
        makeNewField('resource-values');

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

        // Make new property field whenever "add property" button clicked.
        $(document).on('click', '.add-property', function(e) {
            e.preventDefault();
            makeNewField();
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
                var field = $(this).parents('.field');
                var propertyId = propertyLi.data('property-id');
                $('.input-value', field).attr('name', qName + '[' + count + '][@value]');
                $('.input-id', field).val(propertyId).attr('name', qName + '[' + count + '][property_id]');
                $('.input-id', field).attr('data-property-qname', qName);
                var fieldName = cleanText($(this).parent()) + ' (' + cleanText($(this).parents('.vocabulary')) + ')';
                var fieldLabel = $('<label>' + fieldName + '</label>');
                var fieldDesc = $(this).siblings('.description');
                fieldDesc.attr('class', 'field-description');
                $(this).parents('.properties').before(fieldDesc);
                field.find('input[placeholder="Property name"]').replaceWith(fieldLabel);
                field.removeClass('unset');
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
        var newValue = $('.resource-values.field.template .value ').first().clone();
        var count = valuesWrapper.find('.value').length;
        valuesWrapper.find('.value').last().after(newValue);
        var propertyId = valuesWrapper.data('property-id');
        $('.input-id', newValue).val(propertyId).attr('name', qName + '[' + count + '][property_id]');
        $('.input-id', newValue).attr('data-property-qname', qName);
        $('textarea', newValue).attr('name', qName + '[' + count + '][@value]');
        $('html, body').animate({
            scrollTop: (valuesWrapper.offset().top -100)
        },200);
        $('textarea', newValue).focus();
        // elements are counted before the newest is added
        if (count > 0) {
            valuesWrapper.find('.remove-value').addClass('active');
        }
    };
    
    // Duplicates the new field template, and makes it visible by removing the "template" class.
    var makeNewField = function(section,prop,desc) {
        var fieldSection = '#' + section;
        var newField = $(fieldSection + ' .field.template').clone();
        newField.removeClass('template');
        newField.find('.remove-value').removeClass('active');
        if (prop) {
            propertyName = prop.toLowerCase();
            propertyName = propertyName.replace(/ /g, '-');
            newFieldLable = $('<label for="' + propertyName + '">' + prop + '</label>');
            newField.find('[title="new-property-name"]').remove();
            newField.find('.field-meta').prepend(newFieldLable);
            newField.removeClass('new');
        } else {
            newField.addClass('unset');
        }
        if (desc) {
            var descriptionField = $('.field-description').first().clone();
            newField.find('.field-meta label').after(descriptionField);
            newField.find('.o-icon-info + p').text(desc);
        }
        if (prop) {
            $('.new.field').first().before(newField);
        } else {
            //$('.field.template').before(newField);
            $(fieldSection).find('.template').before(newField);
        }
        var modalLink = $('.modal-link');
        if (modalLink.length > 0) {
            attachModal(modalLink);
        }
    };
    
    var attachModal = function(modalLink) {
        var modal = $(modalLink);
        modal.modal({
            trigger: modalLink,
            olay:'div.overlay',
            modals:'div.modal',
            animationEffect: 'fadein',
            animationSpeed: 400,
            moveModalSpeed: 'fast',
            background: '000000',
            opacity: 0.8,
            openOnLoad: false,
            docClose: true,
            closeByEscape: true,
            moveOnScroll: true,
            resizeWindow: true,
            close:'.closeBtn'
        });
    };
    
    var cleanText = function(text) {
        newText = text.clone();
        newText.children().remove();
        newText = newText.text().replace(/^\s+|\s+$/g,'');
        return newText;
    };
    
})(jQuery);
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
            var button_class = $(this).attr('class');
            var nav_id = button_class.replace(/button/, '');
            var nav_object = $('#' + nav_id.replace(/fa-/, ''));
            if ($('header .active').length > 0) {
                if (!($(this).hasClass('active'))) {
                    $('header .active').removeClass('active');
                    $(this).addClass('active');
                    nav_object.addClass('active');
                } else {
                    $('header .active').removeClass('active');
                }
            } else {
                $(this).addClass('active');
                nav_object.addClass('active');
            }
        });
        
        // Variables
        var add_edit_items = $('body.add,body.edit');
        var import_vocabs = $('body.import.vocabs');
        
        // Populate field template with vocabularies, then make a fresh new property field.
        $.getJSON('../javascript/vocabularies.json',function(data) {
            var properties_list_template = $('.template .all-vocabs ul');
            var count = 0;
            $.each(data, function(i) {
                count++;
                $('.template .all-vocabs .count').text(count);
                var current_vocab = $('<li class="vocabulary">' + i + '<ul></ul></li>');
                $.each(data[i][0], function(key, value) {
                    var new_property = $('<li class="property">' + key + '</li>');
                    var property_desc = $('<div class="description"><p class="icon-info"><span class="screen-reader-text">More Info</span></p></div>');
                    property_desc.append($('<p>' + value + '</p>'));
                    var set_property = $('<button class="set-property">Set Property</button>');
                    new_property.append(property_desc);
                    new_property.append(set_property);
                    current_vocab.children('ul').append(new_property);
                });
                properties_list_template.append(current_vocab);                
            });
            
            if ($('#resource-values').length > 0) {
                makeNewField('resource-values');
            }
        });
        
        // Setup tables' select all checkboxes.
        $('label[for="select-all"]').click(function(e) {
            e.preventDefault();
            var select_all = $(this).siblings('input[type="checkbox"]');
            var checkboxes = $(this).parents('table').find('td input[type="checkbox"]');
            var checked = 0;
            checkboxes.each(function() {
                if($(this).prop('checked') == true) {
                    checked = checked + 1;
                }
            });
            if (checked < checkboxes.length) {
                select_all.prop('checked', 'checked');
                checkboxes.each(function() {
                    $(this).prop('checked','checked');
                });
            } else {
                select_all.attr('checked', false);
                checkboxes.each(function() {
                    $(this).removeAttr('checked');
                    $(this).attr('checked', false);
                });
            }
        });

        if ($('body').hasClass('browse')) {
            $('.o-icon-more, .o-icon-delete').click( function(e) {
                e.preventDefault();
                if ($(this).hasClass('o-icon-more')) { $('#more').toggleClass('active') };
                if ($(this).hasClass('o-icon-delete')) { $('#delete').toggleClass('active') };
                if ($('.sidebar').hasClass('active') || !$('div[role="main"]').hasClass('with-  sidebar')) {
                    $('div[role="main"]').addClass('with-sidebar');
                }
                if ($('.sidebar.active').length < 1) {
                    $('div[role="main"]').removeClass('with-sidebar');
                }
            });
            $('.sidebar .o-icon-close').click( function(e) {
                e.preventDefault();
                $(this).parents('.sidebar').removeClass('active');
                if ($('.sidebar.active').length < 1) {
                    $('div[role="main"]').removeClass('with-sidebar');
                }
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
        
        // Switch between section tabs.
        $('a.section, .section legend').click(function(e) {
            e.preventDefault();
            var tab = $(this);
            if (!tab.hasClass('active')) {
                $('.section.active, legend.active').removeClass('active');
                if (tab.is('legend')) {
                    var section_class = tab.parents('.section').attr('id');
                } else {
                    var section_class = tab.attr('class');
                }
                var section_id = section_class.replace(/section/, '');
                tab.addClass('active');
                $('#' + section_id).addClass('active');
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
        
        // In the 'Select Item' modal, toggle item details.
        $(document).on('click', '.modal-content .item a', function(e) {
            e.preventDefault();
            if ($(this).parents('.previewed').length > 0) {
                $('.previewed').removeClass('previewed');
                $('.modal').toggleClass('show-details');
            } else {
                if ($('.previewed').length > 0) {
                    $('.previewed').removeClass('previewed');
                    $(this).parents('.modal .item').addClass('previewed');
                } else {
                    $(this).parents('.modal .item').addClass('previewed');
                    $('.modal').addClass('show-details');
                }
            }
        });
        
        // Carry over any entered value from simple items search to advanced items search.
        add_edit_items.on('click', '.more-options', function(e) {
            e.preventDefault();
            var keywords = $('#item-list-search').val();
            $('input[name="advanced-keyword"').val(keywords);
        });
        
        // Show property descriptions when clicking "more-info" icon.
        add_edit_items.on('click', '.property .icon-info', function() {
            $(this).parents('.description').toggleClass('show');
        });
        
        // Mark existing properties for deletion and straight up remove new properties.
        add_edit_items.on('click', '.remove.button', function(e) {
            e.preventDefault();
            var current_field = $(this).parents('.field');
            if (current_field.hasClass('new')) {
                current_field.remove();
            } else {
                current_field.toggleClass('remove');
            }
        });
        
        // Make new property field whenever "add property" button clicked.
        $(document).on('click', '.add-property', function(e) {
            e.preventDefault();
            makeNewField('resource-values');
        });
        
        
        // Show RDF property sets to choose from.
        add_edit_items.on('click', '.browse-properties', function(e) {
            e.preventDefault();
            $(this).siblings('.properties').toggleClass('show');
        });
        
        // Show properties
        add_edit_items.on('click', '.properties li', function(e) {
            e.stopPropagation();
            if ($(this).children('li')) {
                $(this).toggleClass('show');
            }
        });
        
        // Select property
        add_edit_items.on('click', '.select-property', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var selected_property = $(this).parents('.properties').find('.selected').first();
            if (selected_property.length > 0) {
                selected_property.removeClass('selected');
            }
            $(this).parent().addClass('selected');
        });
        
        // Set property
        add_edit_items.on('click', '.set-property', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var this_field = $(this).parents('.field');
            var field_name = cleanText($(this).parent()) + " (" + cleanText($(this).parents('.vocabulary')) + ")";
            var field_label = $('<label>' + field_name + '</label>');
            var field_desc = $(this).siblings('.description');
            field_desc.attr('class', 'field-description');
            $(this).parents('.properties').before(field_desc);
            this_field.find('input[placeholder="Property name"]').replaceWith(field_label);
            this_field.removeClass('unset');
        });

        
        // Make new value inputs whenever "add value" button clicked.
        add_edit_items.on('click', '.add-value', function(e) {
            e.preventDefault();
            var value_section = '.' + $(this).parents('.section').attr('id');
            var new_value = $(value_section + '.field.template .value ').first().clone();
            $(this).parents('.field').find('.value').last().after(new_value);
            var value_count = $(this).parents('.field').find('.value').length;
            if (value_count == 2) {
                $(this).parents('.field').find('.remove-value').first().addClass('active');
            }
        });
        
        // Remove value.
        add_edit_items.on('click', '.remove-value', function(e) {
            e.preventDefault();
            var this_value = $(this).parents('.value');
            var value_count = $(this).parents('.field').find('.value').length;
            if (value_count > 1) {
                if (value_count == 2) {
                    $(this).parents('.field').find('.remove-value').last().removeClass('active');
                }
                this_value.remove();
            }
        });
        
        // Switch between the different value options.
        $(document).on('click', '.tab', function(e) {
            var tab = $(this);
            e.preventDefault();
            if (!$(this).hasClass('active')) {
                tab.siblings('.tab.active').removeClass('active');
                tab.parent().siblings('.active:not(.remove-value)').removeClass('active');
                var current_class = '.' + tab.attr('class').split(" fa-")[1];
                tab.addClass('active');
                tab.parent().siblings(current_class).addClass('active');
            }
        });
        
        // Load prechosen fields.
        $('select[name="item-type"]').change(function() {
            var item_type = $(this).find('option:selected').text().toLowerCase();
            $.getJSON('RDF.json',function(data) {
                $('#add-item .field:not(.new)').each(function() {
                    if (!$(this).hasClass('keep') && !$(this).hasClass('item-class')) {
                        $(this).remove();
                    }
                });
                if (data[item_type]) {
                    $.each(data[item_type][0], function(key,value) {
                        if ($('label:contains(' + key + ')').length == 0) {
                            makeNewField('item-values',key,value);
                        }
                    });
                }
            })
            .fail(function() {
              console.log( "error" );
            })
            .always(function() {
              console.log( "complete" );
            });
        });
        
        // Keep new fields that have been changed.
        $(document).on('change', '.items .field input', function() {
            $(this).parents('.field').addClass('keep');
        });
    });

    // Duplicates the new field template, and makes it visible by removing the "template" class.
    var makeNewField = function(section,prop,desc) {
        var field_section = '#' + section;
        var new_field = $(field_section + ' .field.template').clone();
        new_field.removeClass('template');
        new_field.find('.remove-value').removeClass('active');
        if (prop) {
            property_name = prop.toLowerCase();
            property_name = property_name.replace(/ /g, "-");
            new_field_label = $('<label for="' + property_name + '">' + prop + '</label>');
            new_field.find('[title="new-property-name"]').remove();
            new_field.find('.field-meta').prepend(new_field_label);
            new_field.removeClass('new');
        } else {
            new_field.addClass('unset');
        }
        if (desc) {
            var description_field = $('.field-description').first().clone();
            new_field.find('.field-meta label').after(description_field);
            new_field.find('.icon-info + p').text(desc);
        }
        if (prop) {
            $('.new.field').first().before(new_field);
        } else {
            //$('.field.template').before(new_field);
            $(field_section).find('.template').before(new_field);
        }
        var modalLink = $('.modal-link');
        if (modalLink.length > 0) {
            attachModal(modalLink);
        }
    }
    
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
    }
    
    var cleanText = function(text) {
        new_text = text.clone();
        new_text.children().remove();
        new_text = new_text.text().replace(/^\s+|\s+$/g,'');
        return new_text;
    }
    
})(jQuery)
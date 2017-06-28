$(document).ready(function() {

var propertyList = $('#properties');

// Enable sorting on property rows.
new Sortable(propertyList[0], {
    draggable: ".property",
    handle: ".sortable-handle"
});

// Add property row via the property selector.
$('#property-selector .selector-child').click(function(event) {
    event.preventDefault();
    var propertyId = $(this).closest('li').data('property-id');
    if ($('#properties li[data-property-id="' + propertyId + '"]').length) {
        // Resource templates cannot be assigned duplicate properties.
        return;
    }
    $.get(propertyList.data('addNewPropertyRowUrl'), {property_id: propertyId})
        .done(function(data) {
            propertyList.append(data);
        });
});

// Remove property via the delete icon.
$('#content').on('click', '.resource-template-property-remove', function(event) {
    event.preventDefault();

    var removeLink = $(this);
    var propertyRow =  removeLink.closest('li.property.row');
    var propertyId = propertyRow.data('property-id');

    // Remove the property ID element from the form.
    var propertyIdElement = propertyRow.children('.property-id-element');
    propertyRow.data('property-id-element', propertyIdElement);
    propertyIdElement.remove();

    // Restore property link.
    var undoRemoveLink = $('<a>', {
        href: '#',
        class: 'fa fa-undo',
        title: Omeka.jsTranslate('Restore property'),
        click: function(event) {
            event.preventDefault();
            propertyRow.toggleClass('delete');
            propertyRow.append(propertyRow.data('property-id-element'));
            removeLink.show();
            $(this).remove();
        },
    });

    propertyRow.toggleClass('delete');
    undoRemoveLink.insertAfter(removeLink);
    removeLink.hide();
});

var propertyEdit = {
    dataTypeOptionsForms: {},
    setDataTypeOptionsForms: function() {
        var optionsForms = {};
        $('#data-type-options-forms > span').each(function() {
            var span = $(this);
            optionsForms[span.data('data-type')] = span.data('options-form')
        });
        this.dataTypeOptionsForms = optionsForms;
    },
    renderDataTypeOptionsForm: function(dataType, options) {
        if (optionsForm = this.dataTypeOptionsForms[dataType]) {
            $('#data-type-options').html(optionsForm);
            try {
                options = JSON.parse(options);
            } catch (e) {
                // Malformed JSON is invalid.
                options = {};
            }
            /**
             * Event "o:data-type-options-form-render"
             *
             * Passed parameters:
             * - (string) The data type
             * - (mixed) The data type options
             * - (DOM object) The data type options form
             */
            $(document).trigger('o:data-type-options-form-render', [
                dataType, options, $('#data-type-options')[0],
            ]);
        } else {
            $('#data-type-options').empty();
        }
    },
    prepareEditForm: function(prop) {
        var altLabel = prop.find('.alternate-label');
        var altComment = prop.find('.alternate-comment');
        var isRequired = prop.find('.is-required');
        var dataType = prop.find('.data-type');
        var dataTypeOptions = prop.find('.data-type-options');

        $('#alternate-label').val(altLabel.val());
        $('#alternate-comment').val(altComment.val());
        $('#is-required').prop('checked', isRequired.val());
        $('#data-type').val(dataType.val());
        this.renderDataTypeOptionsForm(dataType.val(), dataTypeOptions.val());

        $('#set-changes').off().on('click', function(e) {
            altLabel.val($('#alternate-label').val());
            altComment.val($('#alternate-comment').val());
            $('#is-required').prop('checked') ? isRequired.val(1) : isRequired.val(null);
            dataType.val($('#data-type').val());
            /**
             * Event "o:data-type-options-form-set-changes"
             *
             * Passed parameters:
             * - (string) The data type
             * - (DOM object) The data type options form
             * - (DOM object) The data type options hidden input
             */
            $(document).trigger('o:data-type-options-form-set-changes', [
                $('#data-type').val(),
                $('#data-type-options')[0],
                dataTypeOptions[0]
            ]);

            Omeka.closeSidebar($('#edit-sidebar'));
        });

        $('#data-type').off().on('change', function(e) {
            propertyEdit.renderDataTypeOptionsForm(
                $(this).find(':selected').val(),
                dataTypeOptions.val()
            );
        });
    },
};

propertyEdit.setDataTypeOptionsForms();

$('#properties').on('click', '.property-edit', function(e) {
    e.preventDefault();
    propertyEdit.prepareEditForm($(this).closest('.property'));
    Omeka.openSidebar($('#edit-sidebar'));
});

});

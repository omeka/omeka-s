$(document).ready(function() {

// Add the selected item set to the edit panel.
$('#item-set-selector .selector-child').click(function(event) {
    event.preventDefault();

    $('#item-item-sets').removeClass('empty');
    var itemSetId = $(this).data('item-set-id');

    if ($('#item-item-sets').find("input[value='" + itemSetId + "']").length) {
        return;
    }

    var row = $($('#item-set-template').data('template'));
    var itemSetTitle = $(this).data('child-search');
    var ownerEmail = $(this).data('owner-email');
    row.children('td.item-set-title').text(itemSetTitle);
    row.children('td.owner-email').text(ownerEmail);
    row.find('td > input').val(itemSetId);
    $('#item-item-sets > tbody:last').append(row);
});

// Remove an item set from the edit panel.
$('#item-item-sets').on('click', '.o-icon-delete', function(event) {
    event.preventDefault();

    var removeLink = $(this);
    var itemSetRow = $(this).closest('tr');
    var itemSetInput = removeLink.closest('td').find('input');
    itemSetInput.prop('disabled', true);

    // Restore item set link.
    var undoRemoveLink = $('<a>', {
        href: '#',
        class: 'fa fa-undo',
        title: Omeka.jsTranslate('Restore item set'),
        click: function(event) {
            event.preventDefault();
            itemSetRow.toggleClass('delete');
            itemSetInput.prop('disabled', false);
            removeLink.show();
            $(this).remove();
        },
    });

    itemSetRow.toggleClass('delete');
    undoRemoveLink.insertAfter(removeLink);
    removeLink.hide();
});

});

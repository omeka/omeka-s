$(document).ready(function() {

$('#site-selector .selector-child').click(function(event) {
    event.preventDefault();

    $('#item-sites').removeClass('empty');
    var siteId = $(this).data('site-id');

    if ($('#item-sites').find("input[value='" + siteId + "']").length) {
        return;
    }

    var row = $($('#site-template').data('template'));
    console.log(row);
    var siteTitle = $(this).data('child-search');
    var ownerEmail = $(this).data('owner-email');
    row.children('td.site-title').text(siteTitle);
    row.children('td.owner-email').text(ownerEmail);
    row.find('td > input').val(siteId);
    $('#item-sites > tbody:last').append(row);
});

// Remove an item set from the edit panel.
$('#item-sites').on('click', '.o-icon-delete', function(event) {
    event.preventDefault();

    var removeLink = $(this);
    var siteRow = $(this).closest('tr');
    var siteInput = removeLink.closest('td').find('input');
    siteInput.prop('disabled', true);

    // Restore site link.
    var undoRemoveLink = $('<a>', {
        href: '#',
        class: 'fa fa-undo',
        title: Omeka.jsTranslate('Restore site'),
        click: function(event) {
            event.preventDefault();
            siteRow.toggleClass('delete');
            siteInput.prop('disabled', false);
            removeLink.show();
            $(this).remove();
        },
    });

    siteRow.toggleClass('delete');
    undoRemoveLink.insertAfter(removeLink);
    removeLink.hide();
});

});

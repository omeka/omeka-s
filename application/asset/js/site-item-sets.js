$(document).ready(function() {
    
new Sortable(document.getElementById('site-item-set-rows'), {
    draggable: '.site-item-set-row',
    handle: '.sortable-handle',
});

var itemSets = $('#site-item-sets');
var noItemSets = $('#no-site-item-sets');
var itemSetsData = itemSets.data('itemSets');
var rowTemplate = $($.parseHTML(itemSets.data('rowTemplate')));

var appendItemSet = function(id, title, email) {
    if (itemSets.find(".site-item-set-id[value='" + id + "']").length) {
        return;
    }
    var row = rowTemplate.clone();
    row.find('.site-item-set-id').val(id);
    row.find('.site-item-set-title').text(title);
    row.find('.site-item-set-owner-email').text(email);
    $('#site-item-set-rows').append(row);
}

if (itemSetsData.length) {
    $.each(itemSetsData, function() {
        appendItemSet(this.id, this.title, this.email);
    });
    itemSets.show();
} else {
    noItemSets.show();
}

$('#item-set-selector .selector-child').on('click', function() {
    var itemSet = $(this);
    appendItemSet(
        itemSet.data('itemSetId'),
        itemSet.data('childSearch'),
        itemSet.data('ownerEmail')
    );
    itemSets.show();
    noItemSets.hide()
});

itemSets.on('click', '.o-icon-delete', function(e) {
    e.preventDefault();
    var removeLink = $(this);
    var undoLink = removeLink.siblings('.o-icon-undo');
    var row = removeLink.closest('.site-item-set-row');
    row.find('input').prop('disabled', true);
    row.toggleClass('delete');
    removeLink.hide();
    undoLink.show();
});

itemSets.on('click', '.o-icon-undo', function(e) {
    e.preventDefault();
    var undoLink = $(this);
    var removeLink = undoLink.siblings('.o-icon-delete');
    var row = undoLink.closest('.site-item-set-row');
    row.find('input').prop('disabled', false);
    row.toggleClass('delete');
    undoLink.hide();
    removeLink.show();
});

});

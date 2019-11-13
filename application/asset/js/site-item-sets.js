$(document).ready(function() {
    
new Sortable(document.getElementById('site-item-set-rows'), {
    draggable: '.site-item-set-row',
    handle: '.sortable-handle',
});

var itemSets = $('#site-item-sets');
var itemSetsData = itemSets.data('itemSets');
var rowTemplate = $($.parseHTML(itemSets.data('rowTemplate')));
var totalCount = $('.selector-total-count');

var parentToggle = function(e) {
    e.stopPropagation();
    if ($(this).children('li')) {
        $(this).toggleClass('show');
    }
}

var appendItemSet = function(id, title, email) {
    if (itemSets.find(".site-item-set-id[value='" + id + "']").length) {
        return;
    }
    var row = rowTemplate.clone();
    row.find('.site-item-set-id').val(id);
    row.find('.site-item-set-title').text(title);
    row.find('.site-item-set-owner-email').text(email);
    $('#site-item-set-rows').append(row);
    $('[data-item-set-id="' + id + '"]').addClass('added');
    $('#item-sets-section').addClass('has-item-sets');
    updateItemSetCount(id);
}

var updateItemSetCount = function(itemSetId) {
    var itemSet = $('[data-item-set-id="' + itemSetId + '"]');
    var itemSetParent = itemSet.parents('.selector-parent');
    var childCount = itemSetParent.find('.selector-child-count').first();
    if (itemSet.hasClass('added')) {
        var newTotalCount = parseInt(totalCount.text()) - 1;
        var newChildCount = parseInt(childCount.text()) - 1;
    } else {
        var newTotalCount = parseInt(totalCount.text()) + 1;
        var newChildCount = parseInt(childCount.text()) + 1;
    }
    totalCount.text(newTotalCount);
    childCount.text(newChildCount);      
}

if (itemSetsData.length) {
    $.each(itemSetsData, function() {
        appendItemSet(this.id, this.title, this.email);
    });
    $('#item-sets-section').addClass('has-item-sets');
}

$('#item-set-selector .selector-child').on('click', function(e) {
    e.stopPropagation();
    var itemSet = $(this);
    var itemSetParent = itemSet.parents('.selector-parent');
    itemSetParent.unbind('click');
    appendItemSet(
        itemSet.data('itemSetId'),
        itemSet.data('childSearch'),
        itemSet.data('ownerEmail')
    );
    itemSetParent.bind('click', parentToggle);
    Omeka.scrollTo($('.site-item-set-row:last-child'));
});

itemSets.on('click', '.o-icon-delete', function(e) {
    e.preventDefault();
    var row = $(this).closest('.site-item-set-row');
    var itemSetId = row.find('.site-item-set-id').val();
    $('#item-set-selector').find('[data-item-set-id="' + itemSetId + '"]').removeClass('added');
    updateItemSetCount(itemSetId);
    row.remove();
    if ($('.site-item-set-row').length < 1) {
        $('#item-sets-section').removeClass('has-item-sets');
    }
});

});
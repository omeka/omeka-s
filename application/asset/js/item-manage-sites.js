$(document).ready(function() {

var itemSites = $('#item-sites');
var itemSitesData = itemSites.data('item-sites');
var rowTemplate = $($.parseHTML(itemSites.data('rowTemplate')));
var totalCount = $('#site-selector .selector-total-count');

var parentToggle = function(e) {
    e.stopPropagation();
    if ($(this).children('li')) {
        $(this).toggleClass('show');
    }
}

var appendRow = function(id, title, email) {
      if (itemSites.find(".site-id[value='" + id + "']").length) {
        return;
    }
    var row = rowTemplate.clone();
    row.find('.site-id').val(id);
    row.find('.site-title').text(title);
    row.find('.site-owner-email').text(email);
    $('#site-rows').append(row);
    $('[data-site-id="' + id + '"]').addClass('added');
    $('#sites').addClass('has-rows');
    updateSiteCount(id);
}

var updateSiteCount = function(siteId) {
    var site = $('[data-site-id="' + siteId + '"]');
    var siteParent = site.parents('.selector-parent');
    var childCount = siteParent.find('.selector-child-count').first();
    if (site.hasClass('added')) {
        var newTotalCount = parseInt(totalCount.text()) - 1;
        var newChildCount = parseInt(childCount.text()) - 1;
    } else {
        var newTotalCount = parseInt(totalCount.text()) + 1;
        var newChildCount = parseInt(childCount.text()) + 1;
    }
    totalCount.text(newTotalCount);
    childCount.text(newChildCount);
}

if (itemSitesData.length > 0) {
    $.each(itemSitesData, function() {
        appendRow(this.id, this.title, this.email);
        console.log(this.id);
    });
    itemSites.addClass('has-rows');
}

// Add the selected site to the edit panel.
$('#site-selector .selector-child').on('click', function(e) {
    e.stopPropagation();
    
    var site = $(this);
    var siteParent = site.parents('.selector-parent');
    siteParent.unbind('click');
    appendRow(
        site.data('siteId'),
        site.data('childSearch'),
        site.data('ownerEmail')
    );
    siteParent.bind('click', parentToggle);
    Omeka.scrollTo($('.site-row:last-child'));
});

// Remove an item set from the edit panel.
itemSites.on('click', '.o-icon-delete', function(e) {
    e.preventDefault();
    var row = $(this).closest('.site-row');
    var siteId = row.find('.site-id').val();
    $('#site-selector').find('[data-site-id="' + siteId + '"]').removeClass('added');
    updateSiteCount(siteId);
    row.remove();
    if ($('.site-row').length < 1) {
        $('#item-sites').removeClass('has-rows');
    }
});


});

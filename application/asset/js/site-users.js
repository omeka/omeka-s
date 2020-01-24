$(document).ready(function() {

var users = $('#site-user-permissions');
var rowTemplate = $($.parseHTML($('#user-row-template').data('template')));
var totalCount = $('.selector-total-count');

var parentToggle = function(e) {
    e.stopPropagation();
    if ($(this).children('li')) {
        $(this).toggleClass('show');
    }
}

var appendUser = function(id, title, email) {
    if (users.find(".user-id[value='" + id + "']").length) {
        return;
    }
    var index = users.find('.user').length;

    console.log(index);
    var row = rowTemplate.clone();
    row.find(':input').each(function() {
        // Find and replace indexes for all inputs.
        var thisInput = $(this);
        var name = thisInput.attr('name').replace('[__index__]', '[' + index + ']');
        thisInput.attr('name', name);
    });
    row.find('.user-id').val(id);
    row.find('.user-name').text(title + ' ' + email);
    $('#user-rows').append(row);
    $('[data-user-id="' + id + '"]').addClass('added');
    $('#site-form').addClass('has-users');
    updateUserCount(id);
}

var updateUserCount = function(userId) {
    var user = $('[data-user-id="' + userId + '"]');
    var userParent = user.parents('.selector-parent');
    var childCount = userParent.find('.selector-child-count').first();
    if (user.hasClass('added')) {
        var newTotalCount = parseInt(totalCount.text()) - 1;
        var newChildCount = parseInt(childCount.text()) - 1;
    } else {
        var newTotalCount = parseInt(totalCount.text()) + 1;
        var newChildCount = parseInt(childCount.text()) + 1;
    }
    if (newChildCount == 0) {
        userParent.hide()
    } else {
        userParent.show();
    }
    if (newTotalCount == 0) {
        $('#user-selector').addClass('empty');
    } else {
        $('#user-selector').removeClass('empty');
    }
    
    totalCount.text(newTotalCount);
    childCount.text(newChildCount);      
}

if (users.find('.user.value').length) {
    users.find('.user.value').each(function() {
        var userId = $(this).find('.user-id').val();
        $('[data-user-id="' + userId + '"]').addClass('added');
        updateUserCount(userId);
    });
    $('#site-form').addClass('has-users');
}

$('#content').on('click', '.o-icon-delete', function(e) {
    e.preventDefault();
    var row = $(this).closest('.user.value');
    var userId = row.find('.user-id').val();
    $('#user-selector').find('[data-user-id="' + userId + '"]').removeClass('added');
    updateUserCount(userId);
    row.remove();
    if ($('.site-item-set-row').length < 1) {
        $('#item-sets-section').removeClass('has-item-sets');
    }
});

$('.selector .selector-child').click(function(e) {
    e.stopPropagation();
    var user = $(this);
    var userParent = user.parents('.selector-parent');
    userParent.unbind('click');
    appendUser(
        user.data('user-id'),
        user.find('.user-name').text(),
        user.find('.user-email').text()
    );
    userParent.bind('click', parentToggle);
    Omeka.scrollTo($('.user.value:last-child'));
});
});

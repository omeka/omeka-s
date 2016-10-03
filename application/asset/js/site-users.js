$(document).ready(function() {

$('#content').on('click', '.o-icon-delete,.o-icon-undo', function(e) {
    e.preventDefault();
    $(this).parents('tr').toggleClass('delete');
});

$('#content').on('click', '.o-icon-delete', function() {
    $(this).parents('tr').find('input[type="hidden"]').prop('disabled', true);
});

$('#content').on('click', '.o-icon-undo', function() {
    $(this).parents('.user.value').find('input[type="hidden"]').prop('disabled', false);
});

$('.selector .selector-child').click(function() {
    var user = $(this);
    var userId = user.data('user-id');
    var userText = user.data('child-search');
    var permissionsTable = $('#site-user-permissions');
    if (permissionsTable.find('.user-id[value="' + userId + '"]').length) {
        // Do not add existing users.
        return;
    }
    var index = permissionsTable.find('.user').length;
    if (!index) {
        permissionsTable.removeClass('empty');
    }
    var template = $($.parseHTML($('#user-row-template').data('template')));
    template.find('.user-name').text(userText);
    template.find('.user-id').val(userId);
    template.find(':input').each(function() {
        // Find and replace indexes for all inputs.
        var thisInput = $(this);
        var name = thisInput.attr('name').replace('[__index__]', '[' + index + ']');
        thisInput.attr('name', name);
    });
    permissionsTable.find('tbody').append(template);
});

});

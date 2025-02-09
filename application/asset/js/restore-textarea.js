$(document).ready(function() {
    $(document).on('click', 'button.restore-textarea', function(e) {
        var thisButton = $(this);
        thisButton.siblings('textarea').val(thisButton.data('restoreValue'))
    });
});

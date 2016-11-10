$(document).ready(function() {
    $('.selectable-themes .theme').click(function() {
        var theme = $(this);
        if (theme.hasClass('active')) {
            theme.removeClass('active');
        } else {
            $('.selectable-themes .active.theme').removeClass('active');
            theme.addClass('active');
        }
    });
});
$(document).ready(function() {
    $('.selectable-themes .theme').click(function() {
        var theme = $(this);
        if (theme.hasClass('active') && (theme.parents('#site-form').length == 0)) {
            theme.removeClass('active');
            theme.find('[name="o:theme"]').attr('disabled', true);
        } else {
            $('.selectable-themes .active.theme').removeClass('active');
            $('[name="o:theme"]').attr('disabled', true);
            theme.addClass('active');
            theme.find('[name="o:theme"]').removeAttr('disabled');
        }
    });
});
$(document).ready(function() {
    $('.selectable-themes .theme input').not('[disabled]').each(function () {
        $(this).closest('.theme').addClass('active');
    });
    $('.selectable-themes .theme').not('.invalid').click(function() {
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

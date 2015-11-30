(function($) {
    $(document).ready(function() {
        $('header nav').addClass('closed');

        $('header nav').click(function() {
            $(this).toggleClass('open').toggleClass('closed');
        });
    });
})(jQuery);
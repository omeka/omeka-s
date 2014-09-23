$(document).ready(function () {
    // Mobile navigation
    $('#mobile-nav .button').click(function (e) {
        e.preventDefault();
        var button_class = $(this).attr('class');
        var nav_id = button_class.replace(/button/, '');
        var nav_object = $('#' + nav_id.replace(/icon-/, ''));
        if ($('header .active').length > 0) {
            if (!($(this).hasClass('active'))) {
                $('header .active').removeClass('active');
                $(this).addClass('active');
                nav_object.addClass('active');
            } else {
                $('header .active').removeClass('active');
            }
        } else {
            $(this).addClass('active');
            nav_object.addClass('active');
        }
    });
    if ($('body').hasClass('browse')) {
        $('.o-icon-more').click( function(e) {
            e.preventDefault();
            $('#more').toggleClass('active');
        });
        $('.o-icon-delete').click( function(e) {
            e.preventDefault();
            $('#delete').toggleClass('active');
        });
        $('.sidebar .o-icon-close').click( function(e) {
            e.preventDefault();
            $(this).parents('.sidebar').removeClass('active');
        });
    }
});

(function($, window, document) {

    $(function() {

        // Code that depends on the DOM.

        $('.sidebar-details').click(function(e){
            e.preventDefault();
            $('#sidebar-content').empty();
            $('#sidebar-delete-content').hide();
            $('#content').addClass('sidebar-open');
            $.ajax({
                'url': $(this).data('show-details-action'),
                'type': 'get'
            }).done(function(data) {
                $('#sidebar-content').html(data);
            });
        });

        $('.sidebar-delete').click(function(e){
            e.preventDefault();
            $('#sidebar-content').empty();
            $('#sidebar-delete-content').show();
            $('#sidebar-delete-content form').attr(
                'action', $(this).data('delete-action')
            );
            $('#content').addClass('sidebar-open');
            $.ajax({
                'url': $(this).data('show-details-action'),
                'type': 'get'
            }).done(function(data) {
                $('#sidebar-content').html(data);
            });
        });

        $('#sidebar-close').click(function(e) {
            e.preventDefault();
            $('#content').removeClass('sidebar-open');
        });

        // Switch between section tabs.
        $('a.section, .section legend').click(function(e) {
            e.preventDefault();
            var tab = $(this);
            if (!tab.hasClass('active')) {
                $('.section.active, legend.active').removeClass('active');
                if (tab.is('legend')) {
                    var section_class = tab.parents('.section').attr('id');
                } else {
                    var section_class = tab.attr('class');
                }
                var section_id = section_class.replace(/section/, '');
                tab.addClass('active');
                $('#' + section_id).addClass('active');
            }
        });
    });

    // Code that doesn't depend on the DOM.

}(window.jQuery, window, document));

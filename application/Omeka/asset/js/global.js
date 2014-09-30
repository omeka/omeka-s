(function($, window, document) {

    $(function() {

        // Code that depends on the DOM.

        $('.sidebar-details').click(function(e){
            e.preventDefault();
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
    });

    // Code that doesn't depend on the DOM.

}(window.jQuery, window, document));

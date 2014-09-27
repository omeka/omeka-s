(function($, window, document) {

    $(function() {

        // Code that depends on the DOM.

        $('.sidebar-details').click(function(e){
            e.preventDefault();
            $('#content').addClass('sidebar-open');
            $.ajax({
                "url": $(this).data('url'),
                "type": "get"
            }).done(function(data) {
                console.log(data);
                $('#sidebar-content').html(data);
            });
        });

        $('.sidebar-delete').click(function(e){
            e.preventDefault();
        });

        $('#sidebar-close').click(function(e) {
            e.preventDefault();
            $('#content').removeClass('sidebar-open');
        });
    });

    // Code that doesn't depend on the DOM.

}(window.jQuery, window, document));

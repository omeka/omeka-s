(function($, window, document) {

    $(function() {

        // Code that depends on the DOM.

        $('.sidebar-details').click(function(e){
            e.preventDefault();
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
            $('#content').addClass('sidebar-open');
            var deleteAction = $(this).data('delete-action');
            $.ajax({
                'url': $(this).data('show-details-action'),
                'type': 'get'
            }).done(function(data) {
                var deleteForm = $('<form>')
                    .attr({'action': deleteAction, 'method': 'post'})
                    .append('<button>Confirm Delete</button>');
                var sidebarContent = $('#sidebar-content');
                sidebarContent.html(
                    '<h2>Delete Resource</h2>'
                  + '<p>Are you sure you would like to delete this resource?</p>'
                );
                sidebarContent.append(deleteForm);
                sidebarContent.append(data);
            });
        });

        $('#sidebar-close').click(function(e) {
            e.preventDefault();
            $('#content').removeClass('sidebar-open');
        });
    });

    // Code that doesn't depend on the DOM.

}(window.jQuery, window, document));

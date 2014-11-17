(function($, window, document) {

    $(function() {

        // Code that depends on the DOM.

        $('.sidebar-details').click(function(e){
            e.preventDefault();
            $('#content > .sidebar > .sidebar-content').empty();
            openSidebar($('#content > .sidebar'));
            $('#sidebar-delete-content').hide();
            $.ajax({
                'url': $(this).data('show-details-action'),
                'type': 'get'
            }).done(function(data) {
                $('#content > .sidebar > .sidebar-content').html(data);
            });
        });

        $('.sidebar-delete').click(function(e){
            e.preventDefault();
            $('#content > .sidebar > .sidebar-content').empty();
            openSidebar($('#content > .sidebar'));
            $('#sidebar-delete-content').show();
            $('#sidebar-delete-content form').attr(
                'action', $(this).data('delete-action')
            );
            $.ajax({
                'url': $(this).data('show-details-action'),
                'type': 'get'
            }).done(function(data) {
                $('#content > .sidebar > .sidebar-content').html(data);
            });
        });

        $('.sidebar-close').click(function(e) {
            e.preventDefault();
            $(this).parent('.active').removeClass('active');
            if ($('.active.sidebar').length < 1) {
                $('#content').removeClass('sidebar-open');
            }
        });
        
        var openSidebar = function(element) {
            element.addClass('active');
            if (!$('#content').hasClass('sidebar-open')) {
                $('#content').addClass('sidebar-open');
            }
        }

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

(function($) {
    $(document).ready(function() {
        var container = $('#item-pool-container');
        $(document).on('change', '[name="item_assignment_action"]', function() {
            var selectedValue = $(this).val();
            if ((selectedValue == 'no_action') || (selectedValue == 'remove_all')) {
                container.hide();
            } else {
                container.show();
            }
        });

        new Sortable(document.getElementById('site-item-sets'), {
            draggable: '.resource-row',
            handle: '.sortable-handle',
        });
    });
})(jQuery)

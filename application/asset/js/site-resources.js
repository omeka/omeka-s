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

        const saveSearch = $('#save-search');
        const queryFormElement = $('.query-form-element');
        $('#content').on('input', '.query-form-query', function (e) {
            if (queryFormElement.data('query') === $(this).val()) {
                saveSearch.removeClass('active');
            } else {
                saveSearch.addClass('active');
            }
        });

        Omeka.initializeSelector('#site-item-sets', '#item-set-selector');
    });
})(jQuery)

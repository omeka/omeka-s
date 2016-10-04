(function ($) {
    $(document).ready(function () {
        var selectingForm = null;
        var sidebar = $('<div class="sidebar"><div class="sidebar-content"></div></div>');
        sidebar.appendTo('#content');

        $('#content').on('click', '.asset-form-select', function () {
            Omeka.openSidebar(sidebar);
            Omeka.populateSidebarContent(sidebar, $(this).data('sidebar-content-url'));
            selectingForm = $(this).closest('.asset-form-element');
        });

        $('#content').on('click', '.asset-form-clear', function () {
            $(this).closest('.asset-form-element')
                .addClass('empty')
                .find('input[type=hidden]').val('').end()
                .find('.selected-asset').text('');
        });

        $('#content').on('click', '.asset-list .select-asset', function (e) {
            e.preventDefault();
            selectingForm.find('input[type=hidden]').val($(this).data('assetId'));
            selectingForm.find('.selected-asset').text($(this).text());
            selectingForm.removeClass('empty');
            Omeka.closeSidebar(sidebar);
            selectingForm = null;
        });

        $('#content').on('submit', '.asset-upload', function (e) {
            e.preventDefault();
            $.post({
                url: $(this).attr('action'),
                data: new FormData(this),
                contentType: false,
                processData: false
            }).done(function () {
                Omeka.populateSidebarContent(sidebar, selectingForm.find('.asset-form-select').data('sidebar-content-url'));
            }).fail(function () {
                console.log('oops');
            });
        });
    });
})(jQuery);

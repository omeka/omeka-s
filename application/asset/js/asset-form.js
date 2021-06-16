(function ($) {
    $(document).ready(function () {
        var selectingForm = null;
        var sidebar = $('<div class="sidebar" id="asset-sidebar"><div class="sidebar-content"></div></div>');
        sidebar.appendTo('#content');

        $('#content').on('click', '.asset-form-select', function () {
            Omeka.openSidebar(sidebar);
            Omeka.populateSidebarContent(sidebar, $(this).data('sidebar-content-url'));
            selectingForm = $(this).closest('.asset-form-element');
            $(this).addClass('asset-selecting-button');
        });

        $('#content').on('click', '.asset-form-clear', function () {
            $(this).closest('.asset-form-element')
                .addClass('empty')
                .find('input[type=hidden]').val('').end()
                .find('.selected-asset').hide();
        });

        $('#content').on('click', '.asset-list .select-asset', function (e) {
            e.preventDefault();
            var assetSelectingButton = $('.asset-selecting-button');
            var assetOptions = $('#asset-options');
            if (assetOptions.length > 0) {
                assetOptions.addClass('active');
                selectingForm = assetOptions;
                if ($('.add-asset-attachment').hasClass('asset-selecting-button')) {
                    selectingForm.find('.asset-option').val('');
                    $('#asset-options .selected-page').text('');
                    $('#asset-options .selected-page + a').attr('href','');
                    $('#asset-options .none-selected').removeClass('inactive');
                }
            }
            selectingForm.find('.selected-asset-id').val($(this).data('assetId'));
            selectingForm.find('.selected-asset-image').attr('src', $(this).data('assetUrl'));
            selectingForm.find('.selected-asset-name').text($(this).text());
            selectingForm.find('.selected-asset').show();
            selectingForm.removeClass('empty');
            selectingForm = null;
            Omeka.closeSidebar(sidebar);
        });

        $('#content').on('change', '.asset-upload [type="file"]', function() {
            $('.asset-upload button').addClass('active');
        });

        $('#content').on('change', '#filter-owner', function() {
            Omeka.populateSidebarContent(
                sidebar,
                selectingForm.find('.asset-form-select').data('sidebar-content-url'),
                {'owner_id': $(this).val()}
            );
        });

        $('#content').on('submit', '.asset-upload', function (e) {
            var form = $(this);
            e.preventDefault();
            $.post({
                url: form.attr('action'),
                data: new FormData(this),
                contentType: false,
                processData: false
            }).done(function () {
                Omeka.populateSidebarContent(sidebar, $('.asset-selecting-button').data('sidebar-content-url'));
            }).fail(function (jqXHR) {
                var errorList = form.find('ul.errors');
                errorList.empty();
                $.each(JSON.parse(jqXHR.responseText), function () {
                    errorList.append($('<li>', {
                        text: this
                    }));
                })
            });
        });
    });
})(jQuery);

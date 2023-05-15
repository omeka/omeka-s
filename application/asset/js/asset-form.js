(function ($) {
    $(document).ready(function () {
        var selectingForm = null;
        var sidebar = $('<div class="sidebar" id="asset-sidebar"><div class="sidebar-content"></div></div>');
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
                .find('.selected-asset').hide();
        });

        $('#content').on('click', '.asset-list .select-asset', function (e) {
            e.preventDefault();
            selectingForm.find('input[type=hidden]').val($(this).data('assetId'));
            selectingForm.find('.selected-asset-image').attr('src', $(this).data('assetUrl'));
            selectingForm.find('.selected-asset-name').text($(this).text());
            selectingForm.find('.selected-asset').show();
            selectingForm.removeClass('empty');
            Omeka.closeSidebar(sidebar);
            selectingForm = null;
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
                Omeka.populateSidebarContent(sidebar, selectingForm.find('.asset-form-select').data('sidebar-content-url'));
            }).fail(function (jqXHR, textStatus, errorThrown) {
                var errorList = form.find('ul.errors');
                errorList.empty();
                if ('application/json' === jqXHR.getResponseHeader('content-type')) {
                    $.each(JSON.parse(jqXHR.responseText), function () {
                        errorList.append($('<li>', {
                            text: this
                        }));
                    })
                } else {
                    errorList.append($('<li>', {
                        text: errorThrown
                    }));
                }
            });
        });
    });
})(jQuery);

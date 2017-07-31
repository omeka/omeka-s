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

        $('#content').on('change', '.asset-upload [type="file"]', function() {
            $('.asset-upload button').addClass('active');
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

        $('#content').on('click', '.delete-asset', function (e) {
            e.preventDefault();
            $('.cancel-delete').hide();
            $('.delete-asset').show();
            $(this).toggle();
            $(this).siblings('.cancel-delete').toggle();
            var deleteWarning = $('#delete-warning');
            var assetDiv = $(e.target).parents('.asset');
            assetDiv.append(deleteWarning);
            deleteWarning.show();
        });

        $('#content').on('click', '.cancel-delete', function (e) {
            e.preventDefault();
            $(this).toggle();
            $(this).siblings('.delete-asset').toggle();
            var deleteWarning = $('#delete-warning');
            var assetDiv = $(e.target).parents('.asset');
            assetDiv.append(deleteWarning);
            deleteWarning.hide();
        });

        $('#content').on('click', '.confirm-delete', function (e) {
            var assetDiv = $(e.target).parents('.asset');
            var deleteUrl = $('.asset-list').data('delete-url');
            var assetId = assetDiv.data('asset-id');
            
            $.post(deleteUrl, { asset_id: assetId }
            ).done(function () {
                Omeka.populateSidebarContent(sidebar, selectingForm.find('.asset-form-select').data('sidebar-content-url'));
            }).fail(function (jqXHR) {
                var form = $('form.asset-upload');
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

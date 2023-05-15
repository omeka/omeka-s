jQuery(function ($) {
    $('#content').on('change', '.asset-upload input[type="file"]', function() {
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
            window.location.reload();
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

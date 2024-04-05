$(document).ready(function () {
    const container = $('#linked-resources-container');
    const url = container.data('url');
    $.get(url, function(data) {
        container.html(data);
    });
});

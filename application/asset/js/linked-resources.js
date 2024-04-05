$(document).ready(function () {

    // Render linked resources.
    const renderLinkedResources = function(page, resourceProperty) {
        let url = container.data('url');
        searchParams = new URLSearchParams;
        if (page) {
            searchParams.append('page', page);
        }
        if (resourceProperty) {
            searchParams.append('resource_property', resourceProperty);
        }
        url += '?' + searchParams.toString();
        $.get(url, function(data) {
            container.html(data);
        });
    };

    // Render linked resources on initial load.
    const container = $('#linked-resources-container');
    renderLinkedResources();

    // Handle next and previous clicks.
    $(container).on('click', 'a.next, a.previous', function(e) {
        e.preventDefault();
        const thisButton = $(this);
        // Note that we can use any base URL for this purpose.
        const url = new URL(thisButton.attr('href'), 'http://foo');
        renderLinkedResources(
            url.searchParams.get('page'),
            url.searchParams.get('resource_property')
        );
    });

    // Handle page form submission.
    $(container).on('submit', 'form', function(e) {
        e.preventDefault();
        const thisForm = $(this);
        const searchParams = new URLSearchParams(thisForm.serialize());
        renderLinkedResources(
            searchParams.get('page'),
            searchParams.get('resource_property')
        );
    });

    // Handle resource property select.
    $(container).on('change', '#resource-property-select', function(e) {
        const thisSelect = $(this);
        renderLinkedResources('1', thisSelect.val());
    });
});

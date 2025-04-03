$(document).ready(function () {

    // Render linked resources.
    const renderLinkedResources = function(page, resourceProperty) {
        const url = container.data('url');
        const query = {};
        if (page) {
            query.page = page;
        }
        if (resourceProperty) {
            query.resource_property = resourceProperty
        }
        $.get(url, query, function(data) {
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

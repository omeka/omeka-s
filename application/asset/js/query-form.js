$(document).ready(function () {
    let selectingElement;
    const sidebarEdit = $('<div class="sidebar" id="query-sidebar-edit"><div class="sidebar-content"></div></div>');
    const sidebarPreview = $('<div class="sidebar" id="query-sidebar-preview"><div class="sidebar-content"></div></div>');
    sidebarEdit.appendTo('#content');
    sidebarPreview.appendTo('#content');
    $('#content').on('click', '.query-form-edit', function (e) {
        Omeka.closeSidebar(sidebarPreview);
        Omeka.openSidebar(sidebarEdit);
        selectingElement = $(this).closest('.query-form-element');
        const url = selectingElement.data('sidebar-edit-url');
        // The advanced search form will not recognize the first parameter if it begins with "?".
        const query = selectingElement.find('.query-form-query').val().trim().replace(/^\?+/, '');
        const resourceType = selectingElement.data('resourceType');
        Omeka.populateSidebarContent(sidebarEdit, `${url}?${query}&query_resource_type=${resourceType}`);
    });
    $('#content').on('click', '.query-form-show-query', function (e) {
        Omeka.closeSidebar(sidebarEdit);
        Omeka.closeSidebar(sidebarPreview);
        selectingElement = $(this).closest('.query-form-element');
        selectingElement.find('.query-form-query').prop('type', 'text');
        $(this).hide();
        selectingElement.find('.query-form-hide-query').show();
    });
    $('#content').on('click', '.query-form-hide-query', function (e) {
        Omeka.closeSidebar(sidebarEdit);
        Omeka.closeSidebar(sidebarPreview);
        selectingElement = $(this).closest('.query-form-element');
        selectingElement.find('.query-form-query').prop('type', 'hidden');
        $(this).hide();
        selectingElement.find('.query-form-show-query').show();
    });
    $('#content').on('click', '.query-form-restore', function (e) {
        Omeka.closeSidebar(sidebarEdit);
        Omeka.closeSidebar(sidebarPreview);
        selectingElement = $(this).closest('.query-form-element');
        const url = selectingElement.data('searchFiltersUrl');
        const query = selectingElement.data('query');
        $.get(`${url}?${query}`, function(data) {
            selectingElement.find('.query-form-search-filters').html(data);
        });
        selectingElement.find('.query-form-query').val(query);
    });
    $('#content').on('click', '.query-form-set', function (e) {
        Omeka.closeSidebar(sidebarEdit);
        const form = $('#advanced-search');
        Omeka.cleanSearchQuery(form);
        const url = selectingElement.data('searchFiltersUrl');
        const query = form.serialize();
        $.get(`${url}?${query}`, function(data) {
            selectingElement.find('.query-form-search-filters').html(data);
        });
        selectingElement.find('.query-form-query').val(query);
    });
    $('#content').on('click', '.query-form-preview', function (e) {
        Omeka.openSidebar(sidebarPreview);
        const url = selectingElement.data('sidebar-preview-url');
        const query = $('#advanced-search').serialize();
        const resourceType = selectingElement.data('resourceType');
        Omeka.populateSidebarContent(sidebarPreview, `${url}?${query}&query_resource_type=${resourceType}`);
    });
    $('#content').on('click', '.query-form-reset', function (e) {
        Omeka.populateSidebarContent(sidebarEdit, selectingElement.data('sidebar-edit-url'));
    });
});

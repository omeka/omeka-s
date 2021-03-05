$(document).ready(function () {
    let selectingElement;
    const sidebarEdit = $('<div class="sidebar" id="query-sidebar-edit"><div class="sidebar-content"></div></div>');
    const sidebarPreview = $('<div class="sidebar" id="query-sidebar-preview"><div class="sidebar-content"></div></div>');
    sidebarEdit.appendTo('#content');
    sidebarPreview.appendTo('#content');
    $('#content').on('click', '.query-form-edit', function (e) {
        selectingElement = $(this).closest('.query-form-element');
        Omeka.closeSidebar(sidebarPreview);
        Omeka.openSidebar(sidebarEdit);
        const url = selectingElement.data('sidebar-edit-url');
        // The advanced search form will not recognize the first parameter if it begins with "?".
        const query = selectingElement.find('.query-form-query').val().trim().replace(/^\?+/, '');
        Omeka.populateSidebarContent(sidebarEdit, `${url}?${query}`);
    });
    $('#content').on('click', '.query-form-restore', function (e) {
        selectingElement = $(this).closest('.query-form-element');
        selectingElement.find('.query-form-query').val(selectingElement.data('query'));
        Omeka.closeSidebar(sidebarEdit);
        Omeka.closeSidebar(sidebarPreview);
    });
    $('#content').on('click', '.query-form-clear', function (e) {
        selectingElement = $(this).closest('.query-form-element');
        selectingElement.find('.query-form-query').val('');
        Omeka.closeSidebar(sidebarEdit);
        Omeka.closeSidebar(sidebarPreview);
    });
    $('#content').on('click', '.query-form-set', function (e) {
        const form = $('#advanced-search');
        Omeka.cleanSearchQuery(form);
        selectingElement.find('.query-form-query').val(form.serialize());
        Omeka.closeSidebar(sidebarEdit);
    });
    $('#content').on('click', '.query-form-preview', function (e) {
        Omeka.openSidebar(sidebarPreview);
        const url = selectingElement.data('sidebar-preview-url');
        const query = $('#advanced-search').serialize();
        Omeka.populateSidebarContent(sidebarPreview, `${url}?${query}`);
    });
    $('#content').on('click', '.query-form-reset', function (e) {
        Omeka.populateSidebarContent(sidebarEdit, selectingElement.data('sidebar-edit-url'));
    });
});

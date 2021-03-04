$(document).ready(function () {
    let selectingElement;
    const sidebar = $('<div class="sidebar" id="query-sidebar"><div class="sidebar-content"></div></div>');
    sidebar.appendTo('#content');
    $('#content').on('click', '.query-form-edit', function (e) {
        selectingElement = $(this).closest('.query-form-element');
        Omeka.openSidebar(sidebar);
        const url = selectingElement.data('sidebar-content-url');
        // The advanced search form will not recognize the first parameter if it begins with "?".
        const query = selectingElement.find('.query-form-query').val().trim().replace(/^\?+/, '');
        Omeka.populateSidebarContent(sidebar, `${url}?${query}`);
    });
    $('#content').on('click', '.query-form-restore', function (e) {
        selectingElement = $(this).closest('.query-form-element');
        selectingElement.find('.query-form-query').val(selectingElement.data('query'));
        Omeka.closeSidebar(sidebar);
    });
    $('#content').on('click', '.query-form-clear', function (e) {
        selectingElement = $(this).closest('.query-form-element');
        selectingElement.find('.query-form-query').val('');
        Omeka.closeSidebar(sidebar);
    });
    $('#content').on('click', '.query-form-set', function (e) {
        selectingElement.find('.query-form-query').val($('#advanced-search').serialize());
        Omeka.closeSidebar(sidebar);
    });
    $('#content').on('click', '.query-form-preview', function (e) {
        const url = selectingElement.data('previewUrl');
        const query = $('#advanced-search').serialize();
        window.open(`${url}?${query}`, '_blank');
    });
    $('#content').on('click', '.query-form-reset', function (e) {
        Omeka.populateSidebarContent(sidebar, selectingElement.data('sidebar-content-url'));
    });
});

$(document).ready(function () {
    let selectingElement;
    const sidebar = $('<div class="sidebar" id="query-sidebar"><div class="sidebar-content"></div></div>');
    sidebar.appendTo('#content');
    $('#content').on('click', '.query-form-edit', function (e) {
        selectingElement = $(this).closest('.query-form-element');
        Omeka.openSidebar(sidebar);
        Omeka.populateSidebarContent(sidebar, selectingElement.data('sidebar-content-url') + '?' + selectingElement.find('.query-form-text').val());
    });
    $('#content').on('click', '.query-form-restore', function (e) {
        selectingElement = $(this).closest('.query-form-element');
        selectingElement.find('.query-form-text').val(selectingElement.data('query'));
        Omeka.closeSidebar(sidebar);
    });
    $('#content').on('click', '.query-form-clear', function (e) {
        selectingElement = $(this).closest('.query-form-element');
        selectingElement.find('.query-form-text').val('');
        Omeka.closeSidebar(sidebar);
    });
    $('#content').on('click', '.query-form-set', function (e) {
        selectingElement.find('.query-form-text').val($('#advanced-search').serialize());
        Omeka.closeSidebar(sidebar);
    });
    $('#content').on('click', '.query-form-reset', function (e) {
        Omeka.populateSidebarContent(sidebar, selectingElement.data('sidebar-content-url'));
    });
});

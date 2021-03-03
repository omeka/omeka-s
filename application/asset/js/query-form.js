$(document).ready(function () {
    let selectingElement;
    const sidebar = $('<div class="sidebar" id="query-sidebar"><div class="sidebar-content"></div></div>');
    sidebar.appendTo('#content');
    $('#content').on('click', '.query-form-edit', function (e) {
        selectingElement = $(this).closest('.query-form-element');
        Omeka.openSidebar(sidebar);
        Omeka.populateSidebarContent(sidebar, selectingElement.data('sidebar-content-url') + '?' + selectingElement.find('input[type=hidden]').val());
    });
    $('#content').on('click', '.query-form-clear', function (e) {
        selectingElement.find('input[type=hidden]').val('');
        selectingElement.find('.query-form-query').val('');
        Omeka.closeSidebar(sidebar);
    });
    $('#content').on('click', '.query-form-set', function (e) {
        let query = $('#advanced-search').serialize();
        selectingElement.find('input[type=hidden]').val(query);
        selectingElement.find('.query-form-query').val(query);
        Omeka.closeSidebar(sidebar);
    });
    $('#content').on('click', '.query-form-reset', function (e) {
        Omeka.populateSidebarContent(sidebar, selectingElement.data('sidebar-content-url'));
    });
});

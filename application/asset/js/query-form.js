$(document).ready(function () {
    let selectingElement;
    let currentQuery = '';

    // Add sidebars to page.
    const sidebarEdit = $('<div class="sidebar" id="query-sidebar-edit"><div class="sidebar-content"></div></div>');
    const sidebarPreview = $('<div class="sidebar" id="query-sidebar-preview"><div class="sidebar-content"></div></div>');
    sidebarEdit.appendTo('#content');
    sidebarPreview.appendTo('#content');

    // Show or hide button by selector.
    const show = selector => selectingElement.find(selector).removeClass('inactive');
    const hide = selector => selectingElement.find(selector).addClass('inactive');

    // Handle the button that opens the search sidebar..
    $('#content').on('click', '.query-form-edit', function (e) {
        Omeka.closeSidebar(sidebarPreview);
        Omeka.openSidebar(sidebarEdit);
        selectingElement = $(this).closest('.query-form-element');
        const url = selectingElement.data('sidebar-edit-url');
        // The advanced search form will not recognize the first parameter if it begins with "?".
        const query = selectingElement.find('.query-form-query').val().trim().replace(/^\?+/, '');
        Omeka.populateSidebarContent(sidebarEdit, `${url}?${query}`, {
            query_resource_type: selectingElement.data('resourceType'),
            query_partial_excludelist: JSON.stringify(selectingElement.data('partialExcludelist'))
        });
    });

    // Handle the button that shows the query string.
    $('#content').on('click', '.query-form-advanced-edit-show', function (e) {
        Omeka.closeSidebar(sidebarEdit);
        Omeka.closeSidebar(sidebarPreview);
        selectingElement = $(this).closest('.query-form-element');
        currentQuery = selectingElement.find('.query-form-query').val();
        selectingElement.find('.query-form-search-filters').empty();
        selectingElement.find('.query-form-query').prop('type', 'text');
        hide('.query-form-edit');
        hide('.query-form-advanced-edit-show');
        show('.query-form-advanced-edit-apply');
        show('.query-form-advanced-edit-cancel');
        hide('.query-form-restore');
        hide('.query-form-clear');
    });

    // Handle the button that hides the query string.
    $('#content').on('click', '.query-form-advanced-edit-apply', function (e) {
        selectingElement = $(this).closest('.query-form-element');
        currentQuery = '';
        const url = selectingElement.data('searchFiltersUrl');
        const query = selectingElement.find('.query-form-query').val();
        $.get(`${url}?${query}`, function(data) {
            selectingElement.find('.query-form-search-filters').html(data);
        });
        selectingElement.find('.query-form-query').prop('type', 'hidden');
        show('.query-form-edit');
        show('.query-form-advanced-edit-show');
        hide('.query-form-advanced-edit-apply');
        hide('.query-form-advanced-edit-cancel');
        (query === selectingElement.data('query'))
            ? hide('.query-form-restore')
            : show('.query-form-restore');
        (query)
            ? show('.query-form-clear')
            : hide('.query-form-clear');
    });

    $('#content').on('click', '.query-form-advanced-edit-cancel', function (e) {
        selectingElement = $(this).closest('.query-form-element');
        const url = selectingElement.data('searchFiltersUrl');
        const query = currentQuery;
        $.get(`${url}?${query}`, function(data) {
            selectingElement.find('.query-form-search-filters').html(data);
        });
        selectingElement.find('.query-form-query').prop('type', 'hidden').val(currentQuery).trigger('input');
        show('.query-form-edit');
        show('.query-form-advanced-edit-show');
        hide('.query-form-advanced-edit-apply');
        hide('.query-form-advanced-edit-cancel');
        (query === selectingElement.data('query'))
            ? hide('.query-form-restore')
            : show('.query-form-restore');
        (query)
            ? show('.query-form-clear')
            : hide('.query-form-clear');
    });

    // Handle the button that restores the query string to its original state.
    $('#content').on('click', '.query-form-restore', function (e) {
        Omeka.closeSidebar(sidebarEdit);
        Omeka.closeSidebar(sidebarPreview);
        selectingElement = $(this).closest('.query-form-element');
        const url = selectingElement.data('searchFiltersUrl');
        const query = selectingElement.data('query');
        $.get(`${url}?${query}`, function(data) {
            selectingElement.find('.query-form-search-filters').html(data);
        });
        selectingElement.find('.query-form-query').val(query).prop('type', 'hidden').trigger('input');
        selectingElement.find('.query-form-edit').prop('disabled', false);
        (query === selectingElement.data('query'))
            ? hide('.query-form-restore')
            : show('.query-form-restore');
        (query)
            ? show('.query-form-clear')
            : hide('.query-form-clear');
    });

    // Handle the button that clears the query.
    $('#content').on('click', '.query-form-clear', function (e) {
        Omeka.closeSidebar(sidebarEdit);
        Omeka.closeSidebar(sidebarPreview);
        selectingElement = $(this).closest('.query-form-element');
        const url = selectingElement.data('searchFiltersUrl');
        const query = '';
        $.get(`${url}?${query}`, function(data) {
            selectingElement.find('.query-form-search-filters').html(data);
        });
        selectingElement.find('.query-form-query').val('').prop('type', 'hidden').trigger('input');
        selectingElement.find('.query-form-edit').prop('disabled', false);
        (query === selectingElement.data('query'))
            ? hide('.query-form-restore')
            : show('.query-form-restore');
        hide('.query-form-clear');
    });
    // Handle the button that sets the query string from the search sidebar.
    $('#content').on('click', '.query-form-set', function (e) {
        Omeka.closeSidebar(sidebarEdit);
        const form = $('#advanced-search');
        Omeka.cleanSearchQuery(form);
        const url = selectingElement.data('searchFiltersUrl');
        const query = form.serialize();
        $.get(`${url}?${query}`, function(data) {
            selectingElement.find('.query-form-search-filters').html(data);
        });
        selectingElement.find('.query-form-query').val(query).trigger('input');
        (query === selectingElement.data('query'))
            ? hide('.query-form-restore')
            : show('.query-form-restore');
        (query)
            ? show('.query-form-clear')
            : hide('.query-form-clear');
    });

    // Handle the button that opens the preview sidebar.
    $('#content').on('click', '.query-form-preview', function (e) {
        Omeka.openSidebar(sidebarPreview);
        const url = selectingElement.data('sidebar-preview-url');
        const query = $('#advanced-search').serialize();
        Omeka.populateSidebarContent(sidebarPreview, `${url}?${query}`, {
            query_resource_type: selectingElement.data('resourceType'),
            query_preview_append_query: JSON.stringify(selectingElement.data('previewAppendQuery'))
        });
    });

    // handle the button that resets the search sidebar.
    $('#content').on('click', '.query-form-reset', function (e) {
        Omeka.populateSidebarContent(sidebarEdit, selectingElement.data('sidebar-edit-url'), {
            query_resource_type: selectingElement.data('resourceType'),
            query_partial_excludelist: JSON.stringify(selectingElement.data('partialExcludelist'))
        });
    });
});

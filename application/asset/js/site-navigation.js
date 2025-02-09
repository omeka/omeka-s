$(document).ready( function() {

// Initialize the navigation tree
var navTree = $('#nav-tree');
var initialTreeData;
navTree.jstree({
    'core': {
        'check_callback': true,
        'force_text': true,
        'data': navTree.data('jstree-data'),
    },
    'plugins': ['privateStatus', 'dnd', 'removenode', 'editlink', 'display']
}).on('loaded.jstree', function() {
    // Open all nodes by default.
    navTree.jstree(true).open_all();
    initialTreeData = JSON.stringify(navTree.jstree(true).get_json());
}).on('move_node.jstree', function(e, data) {
    // Open node after moving it.
    var parent = navTree.jstree(true).get_node(data.parent);
    navTree.jstree(true).open_all(parent);
});

$('#site-form').on('o:before-form-unload', function () {
    if (initialTreeData !== JSON.stringify(navTree.jstree(true).get_json())) {
        Omeka.markDirty(this);
    }
});

var filterPages = function() {
    var thisInput = $(this);
    var search = thisInput.val().toLowerCase();
    var allPages = $('#nav-page-links .nav-page-link');
    allPages.hide();
    var results = allPages.filter(function() {
        return $(this).attr('data-label').toLowerCase().indexOf(search) >= 0;
    });
    results.show();
};
$('.page-selector-filter').on('keyup', (function() {
    var timer = 0;
    return function() {
        clearTimeout(timer);
        timer = setTimeout(filterPages.bind(this), 400);
    }
})());

});

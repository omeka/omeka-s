(function ($) {
    function loadJStree(index) {
        
        //Initialize unique jsTree for each block
        var navTree = $("[name='o:block[" + index + "][o:layout]']").siblings('.block-pagelist-tree');
        var initialTreeData;
        navTree.jstree({
            'core': {
                "check_callback" : function (operation, node, parent, position, more) {
                    if(operation === "copy_node" || operation === "move_node") {
                        if(more.is_multi) {
                            return false; // prevent moving node to different tree
                        }
                    }
                    return true; // allow everything else
                },
                'data': navTree.data('jstree-data'),
            },
            'plugins': ['dnd', 'removenode', 'display']
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
    }

    $(document).ready(function () {
        var list = document.getElementById('blocks');
        var blockIndex = 0;
        var jstreeIndex = 1;

        $('#blocks .block').each(function () {
            loadJStree(blockIndex);
            blockIndex++;
        });

        $('#blocks').on('o:block-added', '.block', function () {
            loadJStree(blockIndex);
            blockIndex++;
        });
        
        $('form').submit(function(e) {
            $('#blocks .block').each(function(blockIndex) {
                var thisBlock = $(this);
                if (thisBlock.attr('data-block-layout') === 'listOfPages') {
                    // Update listOfPages jstree object
                    // Increment if multiple
                    var jstree = thisBlock.find('.jstree-' + jstreeIndex).jstree()
                    thisBlock.find('.jstree-' + jstreeIndex + ' .jstree-node').each(function(index, element) {
                        //Remove deleted nodes and any children
                        if (element.classList.contains('jstree-removenode-removed')) {
                            jstree.delete_node(element);
                        }; 
                    });
                    thisBlock.find('.jstree-' + jstreeIndex).siblings('.inputs').find(':input[type=hidden]').val(JSON.stringify(jstree.get_json()));
                    jstreeIndex++;
                }
            });
        });

        // Add page select sidebar
        const sidebar = $('<div class="sidebar"><div class="sidebar-content" id="add-pages"></div></div>');
        sidebar.appendTo('#content');
        $('#blocks').on('click', '.site-page-add', function (e) {
            currentTree = $(e.currentTarget).siblings('.jstree').jstree();
            Omeka.populateSidebarContent(
                sidebar,
                $(this).data('sidebar-content-url'),
                {'current_nodes': currentTree.get_json('#', { 'flat': true })}
            );
            Omeka.openSidebar(sidebar);
        });
        
        // Show message if no initial pages
        $('#content').on('o:sidebar-content-loaded', '.sidebar', function(e) {
                var pageLinks = $('#nav-page-links');
                if (!pageLinks.children('.nav-page-link').filter(':visible').length) {
                    pageLinks.siblings('.page-selector-filter').hide();
                    pageLinks.after('<p>' + Omeka.jsTranslate('There are no available pages.') + '</p>');
                }
            });

        // Add a site page link to the block tree
        $('#content').on(
            'click',
            '.nav-page-link',
            $.proxy(function(e) {
                var link = $(e.currentTarget);
                var nodeId = currentTree.create_node('#', {
                    text: link.data('label'),
                    data: {
                        type: link.data('type'),
                        data: {
                            id: link.data('id')
                        }
                    }
                });
                // Remove page links from the available list after they are added.
                link.hide();
                var pageLinks = $('#nav-page-links');
                if (!pageLinks.children('.nav-page-link').filter(':visible').length) {
                    pageLinks.siblings('.page-selector-filter').hide();
                    pageLinks.after('<p>' + Omeka.jsTranslate('There are no available pages.') + '</p>');
                }
            }, this)
        );

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
        $('#content').on(
            'keyup',
            '.page-selector-filter',
            (function() {
            var timer = 0;
            return function() {
                clearTimeout(timer);
                timer = setTimeout(filterPages.bind(this), 400);
            }
        })());
    });
})(window.jQuery);
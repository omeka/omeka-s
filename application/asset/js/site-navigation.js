$(document).ready( function() {

/**
 * RemoveNode Plugin for jsTree
 */
$.jstree.plugins.removenode = function(options, parent) {
    var removeIcon = $('<i>', {
        class: 'jstree-icon jstree-removenode-remove',
        attr:{role:'presentation'}
    });
    var undoIcon = $('<i>', {
        class: 'jstree-icon jstree-removenode-undo',
        attr:{role:'presentation'}
    });
    this.bind = function() {
        parent.bind.call(this);
        this.element.on(
            'click.jstree',
            '.jstree-removenode-remove, .jstree-removenode-undo, .jstree-editnode',
            $.proxy(function(e) {
                var icon = $(e.currentTarget);
                var node = icon.closest('.jstree-node');
                var nodeObj = this.get_node(node);
                var editFields = node.find('.jstree-editlink-container');
                if (icon.hasClass('jstree-editnode')) {
                    node.toggleClass('jstree-editmode');
                    editFields.slideToggle();
                    return;
                } else {
                    icon.hide();
                }
                if (icon.hasClass('jstree-removenode-remove')) {
                    // Handle node removal.
                    icon.siblings('.jstree-removenode-undo').show();
                    node.addClass('jstree-removenode-removed');
                    nodeObj.data.remove = true;
                } else {
                    // Handle undo node removal.
                    icon.siblings('.jstree-removenode-remove').show();
                    node.removeClass('jstree-removenode-removed');
                    nodeObj.data.remove = false;
                }
            }, this)
        );
    };
    this.redraw_node = function(node, deep, is_callback, force_render) {
        node = parent.redraw_node.apply(this, arguments);
        if (node) {
            // Add remove/undo icons to every node.
            var nodeJq = $(node);
            var anchor = nodeJq.children('.jstree-anchor');
            var removeIconClone = removeIcon.clone();
            var undoIconClone = undoIcon.clone();
            anchor.append(removeIconClone);
            anchor.append(undoIconClone);

            // Carry over the removed/not-removed state
            var data = this.get_node(node).data;
            if (data.remove === 'undefined' || data.remove) {
                removeIconClone.hide();
                nodeJq.addClass('jstree-removenode-removed');
            } else {
                undoIconClone.hide();
                nodeJq.removeClass('jstree-removenode-removed');
            }
        }
        return node;
    };
};

/**
 * SiteNavigation plugin for jsTree
 */
$.jstree.plugins.sitenavigation = function(options, parent) {
    var container = $('<div>', {
        class: 'jstree-editlink-container'
    });
    var editNodeIcon = $('<i>', {
        class: 'jstree-icon jstree-editnode',
        attr:{role:'presentation'},
    });
    this.bind = function() {
        parent.bind.call(this);
        // Add a custom page link to the navigation tree.
        $('#nav-custom-pages').on('change', function(e) {
            var thisLink = $(this).children(':selected');
            navTree.jstree(true).create_node('#', {
                data: {
                    type: thisLink.data('type'),
                    label: thisLink.data('label'),
                },
                text: thisLink.data('label')
            });
        });
        // Add a site page link to the navigation tree.
        $('#nav-pages').on('click', '.nav-page', function(e) {
            var thisLink = $(this);
            navTree.jstree(true).create_node('#', {
                data: {
                    type: thisLink.data('type'),
                    label: thisLink.data('label'),
                    id: thisLink.data('id'),
                },
                text: thisLink.data('label')
            });
        });
        // Prepare the navigation tree data for submission.
        $('form').submit(function(e) {
            var instance = navTree.jstree(true);
            navTree.find(':input').each(function(index, element) {
                var nodeObj = instance.get_node(element);
                var element = $(element);
                nodeObj.data[element.data('name')] = element.val()
            });
            $('<input>', {
                'type': 'hidden',
                'name': 'jstree',
                'val': JSON.stringify(instance.get_json())
            }).appendTo('form');
        });
    };
    this.redraw_node = function(node, deep, is_callback, force_render) {
        node = parent.redraw_node.apply(this, arguments);
        if (node) {
            var nodeObj = this.get_node(node);
            if (typeof nodeObj.sitenavigation_container === 'undefined') {
                // The container has not been drawn. Draw it and its contents.
                nodeObj.sitenavigation_container = container.clone();
                $.post(navTree.data('link-form-url'), nodeObj.data)
                    .done(function(data) {
                        nodeObj.sitenavigation_container.append(data);
                    });
            }
            var nodeJq = $(node);
            var anchor = nodeJq.children('.jstree-anchor');
            anchor.append(editNodeIcon.clone());
            nodeJq.children('.jstree-anchor').after(nodeObj.sitenavigation_container);
        }
        return node;
    };
};

// Initialize the navigation tree
var navTree = $('#nav-tree');
navTree.jstree({
    'core': {
        'check_callback': true,
        'data': navTree.data('jstree-data'),
    },
    'plugins': ['dnd','removenode','sitenavigation']
}).on('loaded.jstree', function() {
    // Open all nodes by default.
    navTree.jstree(true).open_all();
}).on('move_node.jstree', function(e, data) {
    // Open node after moving it.
    var parent = navTree.jstree(true).get_node(data.parent);
    navTree.jstree(true).open_all(parent);
});

});

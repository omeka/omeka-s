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
            '.jstree-removenode-remove, .jstree-removenode-undo',
            $.proxy(function(e) {
                var icon = $(e.currentTarget);
                var node = icon.closest('.jstree-node');
                var nodeObj = this.get_node(node);
                icon.hide();
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
 * EditLink plugin for jsTree
 */
$.jstree.plugins.editlink = function(options, parent) {
    var container = $('<div>', {
        class: 'jstree-editlink-container'
    });
    var editIcon = $('<i>', {
        class: 'jstree-icon jstree-editlink-edit',
        attr:{role:'presentation'},
    });
    // Toggle edit link container.
    this.toggleLinkEdit = function(node) {
        var container = node.children('.jstree-editlink-container');
        node.toggleClass('jstree-editlink-editmode');
        container.toggleClass('jstree-editlink-editmode');
        container.slideToggle();
    };
    this.bind = function() {
        parent.bind.call(this);
        // Toggle edit link container when icon is clicked.
        this.element.on(
            'click.jstree',
            '.jstree-editlink-edit',
            $.proxy(function(e) {
                this.toggleLinkEdit($(e.currentTarget).closest('.jstree-node'));
            }, this)
        );
        // Add a custom page link to the navigation tree.
        $('#nav-custom-pages').on(
            'change',
            $.proxy(function(e) {
                var link = $(e.currentTarget).children(':selected');
                var nodeId = this.create_node('#', {
                    text: link.data('label'),
                    data: {
                        type: link.data('type'),
                        data: {
                            label: link.data('label')
                        }
                    }
                });
                this.toggleLinkEdit($('#' + nodeId));
            }, this)
        );
        // Add a site page link to the navigation tree.
        $('#nav-pages').on(
            'click',
            '.nav-page',
            $.proxy(function(e) {
                var link = $(e.currentTarget);
                var nodeId = this.create_node('#', {
                    text: link.data('label'),
                    data: {
                        type: link.data('type'),
                        data: {
                            label: link.data('label'),
                            id: link.data('id')
                        }
                    }
                });
                this.toggleLinkEdit($('#' + nodeId));
            }, this)
        );
        // Prepare the navigation tree data for submission.
        $('#site-form').on(
            'submit',
            $.proxy(function(e) {
                var instance = this;
                $('#nav-tree :input[data-name]').each(function(index, element) {
                    var nodeObj = instance.get_node(element);
                    var element = $(element);
                    nodeObj.data['data'][element.data('name')] = element.val()
                });
                $('<input>', {
                    'type': 'hidden',
                    'name': 'jstree',
                    'val': JSON.stringify(instance.get_json())
                }).appendTo('#site-form');
            }, this)
        );
    };
    this.redraw_node = function(node, deep, is_callback, force_render) {
        node = parent.redraw_node.apply(this, arguments);
        if (node) {
            var nodeObj = this.get_node(node);
            if (typeof nodeObj.editlink_container === 'undefined') {
                // The container has not been drawn. Draw it and its contents.
                nodeObj.editlink_container = container.clone();
                $.post($('#nav-tree').data('link-form-url'), nodeObj.data)
                    .done(function(data) {
                        nodeObj.editlink_container.append(data);
                    });
            }
            var nodeJq = $(node);
            if (nodeObj.editlink_container.hasClass('jstree-editlink-editmode')) {
                // Node should retain the editmode class.
                nodeJq.addClass('jstree-editlink-editmode');
            }
            var anchor = nodeJq.children('.jstree-anchor');
            anchor.append(editIcon.clone());
            nodeJq.children('.jstree-anchor').after(nodeObj.editlink_container);
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
    'plugins': ['dnd','removenode','editlink']
}).on('loaded.jstree', function() {
    // Open all nodes by default.
    navTree.jstree(true).open_all();
}).on('move_node.jstree', function(e, data) {
    // Open node after moving it.
    var parent = navTree.jstree(true).get_node(data.parent);
    navTree.jstree(true).open_all(parent);
});

});

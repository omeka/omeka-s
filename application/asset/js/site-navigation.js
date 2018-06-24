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
                    // Remove required flags else the browser will not submit.
                    var required = node.find(':input[required]');
                    required.attr('data-required', true);
                    required.prop('required', false);
                } else {
                    // Handle undo node removal.
                    icon.siblings('.jstree-removenode-remove').show();
                    node.removeClass('jstree-removenode-removed');
                    nodeObj.data.remove = false;
                    // Restore required flags.
                    var required = node.find(':input[data-required]');
                    required.removeAttr('data-required');
                    required.prop('required', true);
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
        $('#nav-custom-links').on(
            'click',
            'button.option',
            $.proxy(function(e) {
                var link = $(e.currentTarget);
                var nodeId = this.create_node('#', {
                    text: link.text(),
                    data: {
                        type: link.data('type'),
                        data: {}
                    }
                });
                this.toggleLinkEdit($('#' + nodeId));
            }, this)
        );
        // Add a site page link to the navigation tree.
        $('#nav-page-links').on(
            'click',
            '.nav-page-link',
            $.proxy(function(e) {
                var link = $(e.currentTarget);
                var nodeId = this.create_node('#', {
                    text: link.data('label'),
                    data: {
                        type: link.data('type'),
                        data: {
                            id: link.data('id')
                        }
                    }
                });
                // There cannot be duplicate page links in navigation. Remove
                // page links from the available list after they are added.
                link.hide();
                var pageLinks = $(e.delegateTarget);
                if (!pageLinks.children('.nav-page-link').filter(':visible').length) {
                    pageLinks.siblings('.page-selector-filter').hide();
                    pageLinks.after('<p>' + Omeka.jsTranslate('There are no available pages.') + '</p>');
                }
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

        // Open closed nodes if their inputs have validation errors
        document.body.addEventListener('invalid', $.proxy(function (e) {
            var target, section;
            target = $(e.target);
            if (!target.is(':input')) {
                return;
            }
            node = target.closest('.jstree-node');
            if (node.length && !node.hasClass('jstree-editlink-editmode')) {
                this.toggleLinkEdit(node);
            }
        }, this), true);
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

/**
 * Display Plugin for jsTree
 */
$.jstree.plugins.display = function(options, parent) {
    var displayIcon = $('<i>', {
        class: 'jstree-icon jstree-displaylink',
        attr:{role: 'presentation'}
    });
    this.bind = function() {
        parent.bind.call(this);
        this.element.on(
            'click.jstree',
            '.jstree-displaylink',
            $.proxy(function(e) {
                var icon = $(e.currentTarget);
                var node = icon.closest('.jstree-node');
                var nodeObj = this.get_node(node);
                var nodeUrl = nodeObj.data.url;
                window.open(nodeUrl, '_blank');
            }, this)
        );
    };
    this.redraw_node = function(node, deep, is_callback, force_render) {
        node = parent.redraw_node.apply(this, arguments);
        if (node) {
            var nodeObj = this.get_node(node);
            var nodeJq = $(node);
            var anchor = nodeJq.children('.jstree-anchor');
            anchor.append(displayIcon.clone());
        }
        return node;
    };
};

// Initialize the navigation tree
var navTree = $('#nav-tree');
var initialTreeData;
navTree.jstree({
    'core': {
        'check_callback': true,
        'data': navTree.data('jstree-data'),
    },
    'plugins': ['dnd', 'removenode', 'editlink', 'display']
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

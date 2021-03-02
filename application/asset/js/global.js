$(document).ready(function() {
        // Set classes for expandable/collapsible content.
        $(document).on('click', 'a.expand, a.collapse', function(e) {
            e.preventDefault();
            var toggle = $(this);
            toggle.toggleClass('collapse').toggleClass('expand');
            if (toggle.hasClass('expand')) {
                toggle.attr('aria-label', Omeka.jsTranslate('Expand')).attr('title', Omeka.jsTranslate('Expand'));
                toggle.trigger('o:collapsed');
            } else {
                toggle.attr('aria-label', Omeka.jsTranslate('Collapse')).attr('title', Omeka.jsTranslate('Collapse'));
                toggle.trigger('o:expanded');
            }
        });
});

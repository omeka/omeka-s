(function($) {
    function fixIframeAspect() {
        $('iframe').each(function () {
            var aspect = $(this).attr('height') / $(this).attr('width');
            $(this).height($(this).width() * aspect);
        });
    }

    function framerateCallback(callback) {
        var waiting = false;
        callback = callback.bind(this);
        return function () {
            if (!waiting) {
                waiting = true;
                window.requestAnimationFrame(function () {
                    callback();
                    waiting = false;
                });
            }
        }
    }

    $(document).ready(function() {
        $('header nav').addClass('closed');

        $('header nav').click(function() {
            $(this).toggleClass('open').toggleClass('closed');
        });
        
        var expandString = Omeka.jsTranslate('Expand');
        var collapseString = Omeka.jsTranslate('Collapse');

        $('header nav ul ul').each(function(){
          var childMenu = $(this);
          var parentItem = childMenu.parent('li');
          var toggleButton = $('<button type="button" class="child-toggle"></button>');
          toggleButton.attr('aria-label', expandString);
          parentItem.addClass('parent');
          parentItem.children('a').first().wrap('<div class="parent-link"></div>');
          parentItem.find('.parent-link').append(toggleButton);
        });
        
        $('header nav').on('click', '.child-toggle', function(e) {
          e.stopPropagation();
          var childToggle = $(this);
          var childMenu = childToggle.parents('.parent').first().find('ul').first();
          childMenu.toggleClass('open');
          if (childMenu.hasClass('open')) {
            childToggle.attr('aria-label', collapseString);
          } else {
            childToggle.attr('aria-label', expandString);
          }
        });
        
        // Maintain iframe aspect ratios
        $(window).on('load resize', framerateCallback(fixIframeAspect));
        fixIframeAspect();
    });
})(jQuery);

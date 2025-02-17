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
        var navElement = $('header nav');
        var expandString = Omeka.jsTranslate('Expand');
        var collapseString = Omeka.jsTranslate('Collapse');

        var closeChildNav = function(parentLi) {
            var childToggle = parentLi.find('.child-toggle').first();
            var childMenu = parentLi.find('ul').first();
            childMenu.removeClass('open');
            childToggle.removeClass('open');
            childToggle.attr('aria-label', expandString).attr('aria-expanded', "false");
        };

        var openChildNav = function(parentLi) {
            var childToggle = parentLi.find('.child-toggle').first();
            var childMenu = parentLi.find('ul').first();
            childMenu.addClass('open');
            childToggle.addClass('open');
            childToggle.attr('aria-label', collapseString).attr('aria-expanded', "true");
        };

        navElement.on('click', '#mobile-nav-toggle', function() {
            navElement.toggleClass('open');
            if (navElement.hasClass('open')) {
                $(this).attr('aria-expanded', "true");
            } else {
                $(this).attr('aria-expanded', "false");
            }
        });
        
        navElement.find('ul ul').each(function(){
          var childMenu = $(this);
          var parentItem = childMenu.parent('li');
          var toggleButton = $('<button type="button" class="child-toggle" aria-expanded="false"></button>');
          toggleButton.attr('aria-label', expandString);
          parentItem.addClass('parent');
          parentItem.children('a').first().wrap('<div class="parent-link"></div>');
          parentItem.find('.parent-link').append(toggleButton);
        });
        
        navElement.on('click', '.child-toggle', function() {
          var parentLi = $(this).parents('.parent').first();
          if ($(this).hasClass('open')) {
            closeChildNav(parentLi);
          } else {
            openChildNav(parentLi);
          }
        });

        navElement.on('mouseenter', '.parent', function() {            
            openChildNav($(this));
        });

        navElement.on('mouseleave', '.parent', function() {            
            closeChildNav($(this));
        });

        navElement.on('mouseleave', '.child-toggle', function() {            
            var parentLi = $(this).parents('.parent').first();
            closeChildNav(parentLi);
        });

        navElement.on('keydown', '.open li:last-child > a:only-child', function(e) {
            if ((e.keyCode == "9") && !e.shiftKey) {
                e.preventDefault();
                var parentLi = $(this).parents('.parent').first();
                var nextParentLi = parentLi.next().find('a').first();
                if (nextParentLi.length > 0) {
                    nextParentLi.focus();
                } else {
                    $('#search-form input:first-child').focus();
                }
                closeChildNav(parentLi);
            }
        });

        navElement.on('keydown', '.navigation > .parent > .parent-link > a', function(e) {
            if ((e.keyCode == "9") && e.shiftKey) {
                var parentLi = $(this).parents('.parent').first();
                closeChildNav(parentLi);
            }
        });
        
        // Maintain iframe aspect ratios
        $(window).on('load resize', framerateCallback(fixIframeAspect));
        fixIframeAspect();
    });
})(jQuery);

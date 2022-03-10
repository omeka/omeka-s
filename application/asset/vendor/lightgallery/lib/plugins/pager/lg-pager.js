"use strict";
var __assign = (this && this.__assign) || function () {
    __assign = Object.assign || function(t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
            s = arguments[i];
            for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p))
                t[p] = s[p];
        }
        return t;
    };
    return __assign.apply(this, arguments);
};
Object.defineProperty(exports, "__esModule", { value: true });
var lg_events_1 = require("../../lg-events");
var lg_pager_settings_1 = require("./lg-pager-settings");
var Pager = /** @class */ (function () {
    function Pager(instance, $LG) {
        // get lightGallery core plugin instance
        this.core = instance;
        this.$LG = $LG;
        // extend module default settings with lightGallery core settings
        this.settings = __assign(__assign({}, lg_pager_settings_1.pagerSettings), this.core.settings);
        return this;
    }
    Pager.prototype.getPagerHtml = function (items) {
        var pagerList = '';
        for (var i = 0; i < items.length; i++) {
            pagerList += "<span  data-lg-item-id=\"" + i + "\" class=\"lg-pager-cont\"> \n                    <span data-lg-item-id=\"" + i + "\" class=\"lg-pager\"></span>\n                    <div class=\"lg-pager-thumb-cont\"><span class=\"lg-caret\"></span> <img src=\"" + items[i].thumb + "\" /></div>\n                    </span>";
        }
        return pagerList;
    };
    Pager.prototype.init = function () {
        var _this = this;
        if (!this.settings.pager) {
            return;
        }
        var timeout;
        this.core.$lgComponents.prepend('<div class="lg-pager-outer"></div>');
        var $pagerOuter = this.core.outer.find('.lg-pager-outer');
        $pagerOuter.html(this.getPagerHtml(this.core.galleryItems));
        // @todo enable click
        $pagerOuter.first().on('click.lg touchend.lg', function (event) {
            var $target = _this.$LG(event.target);
            if (!$target.hasAttribute('data-lg-item-id')) {
                return;
            }
            var index = parseInt($target.attr('data-lg-item-id'));
            _this.core.slide(index, false, true, false);
        });
        $pagerOuter.first().on('mouseover.lg', function () {
            clearTimeout(timeout);
            $pagerOuter.addClass('lg-pager-hover');
        });
        $pagerOuter.first().on('mouseout.lg', function () {
            timeout = setTimeout(function () {
                $pagerOuter.removeClass('lg-pager-hover');
            });
        });
        this.core.LGel.on(lg_events_1.lGEvents.beforeSlide + ".pager", function (event) {
            var index = event.detail.index;
            _this.manageActiveClass.call(_this, index);
        });
        this.core.LGel.on(lg_events_1.lGEvents.updateSlides + ".pager", function () {
            $pagerOuter.empty();
            $pagerOuter.html(_this.getPagerHtml(_this.core.galleryItems));
            _this.manageActiveClass(_this.core.index);
        });
    };
    Pager.prototype.manageActiveClass = function (index) {
        var $pagerCont = this.core.outer.find('.lg-pager-cont');
        $pagerCont.removeClass('lg-pager-active');
        $pagerCont.eq(index).addClass('lg-pager-active');
    };
    Pager.prototype.destroy = function () {
        this.core.outer.find('.lg-pager-outer').remove();
        this.core.LGel.off('.lg.pager');
        this.core.LGel.off('.pager');
    };
    return Pager;
}());
exports.default = Pager;
//# sourceMappingURL=lg-pager.js.map
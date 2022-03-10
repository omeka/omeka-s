"use strict";
/**
 * lightGallery caption for placing captions relative to the image
 */
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
var lg_relative_caption_settings_1 = require("./lg-relative-caption-settings");
var RelativeCaption = /** @class */ (function () {
    function RelativeCaption(instance) {
        // get lightGallery core plugin instance
        this.core = instance;
        // Override some of lightGallery default settings
        var defaultSettings = {
            addClass: this.core.settings.addClass + ' lg-relative-caption',
        };
        this.core.settings = __assign(__assign({}, this.core.settings), defaultSettings);
        // extend module default settings with lightGallery core settings
        this.settings = __assign(__assign(__assign({}, lg_relative_caption_settings_1.relativeCaptionSettings), this.core.settings), defaultSettings);
        return this;
    }
    RelativeCaption.prototype.init = function () {
        var _this = this;
        if (!this.settings.relativeCaption) {
            return;
        }
        this.core.LGel.on(lg_events_1.lGEvents.slideItemLoad + ".caption", function (event) {
            var _a = event.detail, index = _a.index, delay = _a.delay;
            setTimeout(function () {
                if (index === _this.core.index) {
                    _this.setRelativeCaption(index);
                }
            }, delay);
        });
        this.core.LGel.on(lg_events_1.lGEvents.afterSlide + ".caption", function (event) {
            var index = event.detail.index;
            setTimeout(function () {
                var slide = _this.core.getSlideItem(index);
                if (slide.hasClass('lg-complete')) {
                    _this.setRelativeCaption(index);
                }
            });
        });
        this.core.LGel.on(lg_events_1.lGEvents.beforeSlide + ".caption", function (event) {
            var index = event.detail.index;
            setTimeout(function () {
                var slide = _this.core.getSlideItem(index);
                slide.removeClass('lg-show-caption');
            });
        });
        this.core.LGel.on(lg_events_1.lGEvents.containerResize + ".caption", function (event) {
            _this.setRelativeCaption(_this.core.index);
        });
    };
    RelativeCaption.prototype.setCaptionStyle = function (index, rect, slideWrapRect) {
        var $subHtmlInner = this.core
            .getSlideItem(index)
            .find('.lg-relative-caption-item');
        var $subHtml = this.core.getSlideItem(index).find('.lg-sub-html');
        $subHtml.css('width', rect.width + "px").css('left', rect.left + "px");
        var subHtmlRect = $subHtmlInner.get().getBoundingClientRect();
        var bottom = slideWrapRect.bottom - rect.bottom - subHtmlRect.height;
        $subHtml.css('top', "auto").css('bottom', Math.max(bottom, 0) + "px");
    };
    RelativeCaption.prototype.setRelativeCaption = function (index) {
        var slide = this.core.getSlideItem(index);
        if (slide.hasClass('lg-current')) {
            var rect = this.core
                .getSlideItem(index)
                .find('.lg-object')
                .get()
                .getBoundingClientRect();
            var slideWrapRect = this.core
                .getSlideItem(index)
                .get()
                .getBoundingClientRect();
            this.setCaptionStyle(index, rect, slideWrapRect);
            slide.addClass('lg-show-caption');
        }
    };
    RelativeCaption.prototype.destroy = function () {
        this.core.LGel.off('.caption');
    };
    return RelativeCaption;
}());
exports.default = RelativeCaption;
//# sourceMappingURL=lg-relative-caption.js.map
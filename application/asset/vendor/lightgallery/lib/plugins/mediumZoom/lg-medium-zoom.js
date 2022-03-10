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
var lg_medium_zoom_settings_1 = require("./lg-medium-zoom-settings");
var MediumZoom = /** @class */ (function () {
    function MediumZoom(instance, $LG) {
        var _this = this;
        // get lightGallery core plugin instance
        this.core = instance;
        this.$LG = $LG;
        // Set margin
        this.core.getMediaContainerPosition = function () {
            return {
                top: _this.settings.margin,
                bottom: _this.settings.margin,
            };
        };
        // Override some of lightGallery default settings
        var defaultSettings = {
            controls: false,
            download: false,
            counter: false,
            showCloseIcon: false,
            extraProps: ['lgBackgroundColor'],
            closeOnTap: false,
            enableSwipe: false,
            enableDrag: false,
            swipeToClose: false,
            addClass: this.core.settings.addClass + ' lg-medium-zoom',
        };
        this.core.settings = __assign(__assign({}, this.core.settings), defaultSettings);
        // extend module default settings with lightGallery core settings
        this.settings = __assign(__assign(__assign({}, lg_medium_zoom_settings_1.mediumZoomSettings), this.core.settings), defaultSettings);
        return this;
    }
    MediumZoom.prototype.toggleItemClass = function () {
        for (var index = 0; index < this.core.items.length; index++) {
            var $element = this.$LG(this.core.items[index]);
            $element.toggleClass('lg-medium-zoom-item');
        }
    };
    MediumZoom.prototype.init = function () {
        var _this = this;
        if (!this.settings.mediumZoom) {
            return;
        }
        this.core.LGel.on(lg_events_1.lGEvents.beforeOpen + ".medium", function () {
            _this.core.$backdrop.css('background-color', _this.core.galleryItems[_this.core.index].lgBackgroundColor ||
                _this.settings.backgroundColor);
        });
        this.toggleItemClass();
        this.core.outer.on('click.lg.medium', function () {
            _this.core.closeGallery();
        });
    };
    MediumZoom.prototype.destroy = function () {
        this.toggleItemClass();
    };
    return MediumZoom;
}());
exports.default = MediumZoom;
//# sourceMappingURL=lg-medium-zoom.js.map
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
var lg_rotate_settings_1 = require("./lg-rotate-settings");
var Rotate = /** @class */ (function () {
    function Rotate(instance, $LG) {
        // get lightGallery core plugin instance
        this.core = instance;
        this.$LG = $LG;
        // extend module default settings with lightGallery core settings
        this.settings = __assign(__assign({}, lg_rotate_settings_1.rotateSettings), this.core.settings);
        return this;
    }
    Rotate.prototype.buildTemplates = function () {
        var rotateIcons = '';
        if (this.settings.flipVertical) {
            rotateIcons += "<button type=\"button\" id=\"lg-flip-ver\" aria-label=\"" + this.settings.rotatePluginStrings['flipVertical'] + "\" class=\"lg-flip-ver lg-icon\"></button>";
        }
        if (this.settings.flipHorizontal) {
            rotateIcons += "<button type=\"button\" id=\"lg-flip-hor\" aria-label=\"" + this.settings.rotatePluginStrings['flipHorizontal'] + "\" class=\"lg-flip-hor lg-icon\"></button>";
        }
        if (this.settings.rotateLeft) {
            rotateIcons += "<button type=\"button\" id=\"lg-rotate-left\" aria-label=\"" + this.settings.rotatePluginStrings['rotateLeft'] + "\" class=\"lg-rotate-left lg-icon\"></button>";
        }
        if (this.settings.rotateRight) {
            rotateIcons += "<button type=\"button\" id=\"lg-rotate-right\" aria-label=\"" + this.settings.rotatePluginStrings['rotateRight'] + "\" class=\"lg-rotate-right lg-icon\"></button>";
        }
        this.core.$toolbar.append(rotateIcons);
    };
    Rotate.prototype.init = function () {
        var _this = this;
        if (!this.settings.rotate) {
            return;
        }
        this.buildTemplates();
        // Save rotate config for each item to persist its rotate, flip values
        // even after navigating to diferent slides
        this.rotateValuesList = {};
        // event triggered after appending slide content
        this.core.LGel.on(lg_events_1.lGEvents.afterAppendSlide + ".rotate", function (event) {
            var index = event.detail.index;
            var imageWrap = _this.core
                .getSlideItem(index)
                .find('.lg-img-wrap')
                .first();
            imageWrap.wrap('lg-img-rotate');
            _this.core
                .getSlideItem(_this.core.index)
                .find('.lg-img-rotate')
                .css('transition-duration', _this.settings.rotateSpeed + 'ms');
        });
        this.core.outer
            .find('#lg-rotate-left')
            .first()
            .on('click.lg', this.rotateLeft.bind(this));
        this.core.outer
            .find('#lg-rotate-right')
            .first()
            .on('click.lg', this.rotateRight.bind(this));
        this.core.outer
            .find('#lg-flip-hor')
            .first()
            .on('click.lg', this.flipHorizontal.bind(this));
        this.core.outer
            .find('#lg-flip-ver')
            .first()
            .on('click.lg', this.flipVertical.bind(this));
        // Reset rotate on slide change
        this.core.LGel.on(lg_events_1.lGEvents.beforeSlide + ".rotate", function (event) {
            if (!_this.rotateValuesList[event.detail.index]) {
                _this.rotateValuesList[event.detail.index] = {
                    rotate: 0,
                    flipHorizontal: 1,
                    flipVertical: 1,
                };
            }
        });
    };
    Rotate.prototype.applyStyles = function () {
        var $image = this.core
            .getSlideItem(this.core.index)
            .find('.lg-img-rotate')
            .first();
        $image.css('transform', 'rotate(' +
            this.rotateValuesList[this.core.index].rotate +
            'deg)' +
            ' scale3d(' +
            this.rotateValuesList[this.core.index].flipHorizontal +
            ', ' +
            this.rotateValuesList[this.core.index].flipVertical +
            ', 1)');
    };
    Rotate.prototype.rotateLeft = function () {
        this.rotateValuesList[this.core.index].rotate -= 90;
        this.applyStyles();
        this.triggerEvents(lg_events_1.lGEvents.rotateLeft, {
            rotate: this.rotateValuesList[this.core.index].rotate,
        });
    };
    Rotate.prototype.rotateRight = function () {
        this.rotateValuesList[this.core.index].rotate += 90;
        this.applyStyles();
        this.triggerEvents(lg_events_1.lGEvents.rotateRight, {
            rotate: this.rotateValuesList[this.core.index].rotate,
        });
    };
    Rotate.prototype.getCurrentRotation = function (el) {
        if (!el) {
            return 0;
        }
        var st = this.$LG(el).style();
        var tm = st.getPropertyValue('-webkit-transform') ||
            st.getPropertyValue('-moz-transform') ||
            st.getPropertyValue('-ms-transform') ||
            st.getPropertyValue('-o-transform') ||
            st.getPropertyValue('transform') ||
            'none';
        if (tm !== 'none') {
            var values = tm.split('(')[1].split(')')[0].split(',');
            if (values) {
                var angle = Math.round(Math.atan2(values[1], values[0]) * (180 / Math.PI));
                return angle < 0 ? angle + 360 : angle;
            }
        }
        return 0;
    };
    Rotate.prototype.flipHorizontal = function () {
        var rotateEl = this.core
            .getSlideItem(this.core.index)
            .find('.lg-img-rotate')
            .first()
            .get();
        var currentRotation = this.getCurrentRotation(rotateEl);
        var rotateAxis = 'flipHorizontal';
        if (currentRotation === 90 || currentRotation === 270) {
            rotateAxis = 'flipVertical';
        }
        this.rotateValuesList[this.core.index][rotateAxis] *= -1;
        this.applyStyles();
        this.triggerEvents(lg_events_1.lGEvents.flipHorizontal, {
            flipHorizontal: this.rotateValuesList[this.core.index][rotateAxis],
        });
    };
    Rotate.prototype.flipVertical = function () {
        var rotateEl = this.core
            .getSlideItem(this.core.index)
            .find('.lg-img-rotate')
            .first()
            .get();
        var currentRotation = this.getCurrentRotation(rotateEl);
        var rotateAxis = 'flipVertical';
        if (currentRotation === 90 || currentRotation === 270) {
            rotateAxis = 'flipHorizontal';
        }
        this.rotateValuesList[this.core.index][rotateAxis] *= -1;
        this.applyStyles();
        this.triggerEvents(lg_events_1.lGEvents.flipVertical, {
            flipVertical: this.rotateValuesList[this.core.index][rotateAxis],
        });
    };
    Rotate.prototype.triggerEvents = function (event, detail) {
        var _this = this;
        setTimeout(function () {
            _this.core.LGel.trigger(event, detail);
        }, this.settings.rotateSpeed + 10);
    };
    Rotate.prototype.isImageOrientationChanged = function () {
        var rotateValue = this.rotateValuesList[this.core.index];
        var isRotated = Math.abs(rotateValue.rotate) % 360 !== 0;
        var ifFlippedHor = rotateValue.flipHorizontal < 0;
        var ifFlippedVer = rotateValue.flipVertical < 0;
        return isRotated || ifFlippedHor || ifFlippedVer;
    };
    Rotate.prototype.closeGallery = function () {
        if (this.isImageOrientationChanged()) {
            this.core.getSlideItem(this.core.index).css('opacity', 0);
        }
        this.rotateValuesList = {};
    };
    Rotate.prototype.destroy = function () {
        // Unbind all events added by lightGallery rotate plugin
        this.core.LGel.off('.lg.rotate');
        this.core.LGel.off('.rotate');
    };
    return Rotate;
}());
exports.default = Rotate;
//# sourceMappingURL=lg-rotate.js.map
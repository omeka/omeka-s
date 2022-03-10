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
var lg_autoplay_settings_1 = require("./lg-autoplay-settings");
/**
 * Creates the autoplay plugin.
 * @param {object} element - lightGallery element
 */
var Autoplay = /** @class */ (function () {
    function Autoplay(instance) {
        this.core = instance;
        // extend module default settings with lightGallery core settings
        this.settings = __assign(__assign({}, lg_autoplay_settings_1.autoplaySettings), this.core.settings);
        return this;
    }
    Autoplay.prototype.init = function () {
        var _this = this;
        if (!this.settings.autoplay) {
            return;
        }
        this.interval = false;
        // Identify if slide happened from autoplay
        this.fromAuto = true;
        // Identify if autoplay canceled from touch/drag
        this.pausedOnTouchDrag = false;
        this.pausedOnSlideChange = false;
        // append autoplay controls
        if (this.settings.autoplayControls) {
            this.controls();
        }
        // Create progress bar
        if (this.settings.progressBar) {
            this.core.outer.append('<div class="lg-progress-bar"><div class="lg-progress"></div></div>');
        }
        // Start autoplay
        if (this.settings.slideShowAutoplay) {
            this.core.LGel.once(lg_events_1.lGEvents.slideItemLoad + ".autoplay", function () {
                _this.startAutoPlay();
            });
        }
        // cancel interval on touchstart and dragstart
        this.core.LGel.on(lg_events_1.lGEvents.dragStart + ".autoplay touchstart.lg.autoplay", function () {
            if (_this.interval) {
                _this.stopAutoPlay();
                _this.pausedOnTouchDrag = true;
            }
        });
        // restore autoplay if autoplay canceled from touchstart / dragstart
        this.core.LGel.on(lg_events_1.lGEvents.dragEnd + ".autoplay touchend.lg.autoplay", function () {
            if (!_this.interval && _this.pausedOnTouchDrag) {
                _this.startAutoPlay();
                _this.pausedOnTouchDrag = false;
            }
        });
        this.core.LGel.on(lg_events_1.lGEvents.beforeSlide + ".autoplay", function () {
            _this.showProgressBar();
            if (!_this.fromAuto && _this.interval) {
                _this.stopAutoPlay();
                _this.pausedOnSlideChange = true;
            }
            else {
                _this.pausedOnSlideChange = false;
            }
            _this.fromAuto = false;
        });
        // restore autoplay if autoplay canceled from touchstart / dragstart
        this.core.LGel.on(lg_events_1.lGEvents.afterSlide + ".autoplay", function () {
            if (_this.pausedOnSlideChange &&
                !_this.interval &&
                _this.settings.forceSlideShowAutoplay) {
                _this.startAutoPlay();
                _this.pausedOnSlideChange = false;
            }
        });
        // set progress
        this.showProgressBar();
    };
    Autoplay.prototype.showProgressBar = function () {
        var _this = this;
        if (this.settings.progressBar && this.fromAuto) {
            var _$progressBar_1 = this.core.outer.find('.lg-progress-bar');
            var _$progress_1 = this.core.outer.find('.lg-progress');
            if (this.interval) {
                _$progress_1.removeAttr('style');
                _$progressBar_1.removeClass('lg-start');
                setTimeout(function () {
                    _$progress_1.css('transition', 'width ' +
                        (_this.core.settings.speed +
                            _this.settings.slideShowInterval) +
                        'ms ease 0s');
                    _$progressBar_1.addClass('lg-start');
                }, 20);
            }
        }
    };
    // Manage autoplay via play/stop buttons
    Autoplay.prototype.controls = function () {
        var _this = this;
        var _html = "<button aria-label=\"" + this.settings.autoplayPluginStrings['toggleAutoplay'] + "\" type=\"button\" class=\"lg-autoplay-button lg-icon\"></button>";
        // Append autoplay controls
        this.core.outer
            .find(this.settings.appendAutoplayControlsTo)
            .append(_html);
        this.core.outer
            .find('.lg-autoplay-button')
            .first()
            .on('click.lg.autoplay', function () {
            if (_this.core.outer.hasClass('lg-show-autoplay')) {
                _this.stopAutoPlay();
            }
            else {
                if (!_this.interval) {
                    _this.startAutoPlay();
                }
            }
        });
    };
    // Autostart gallery
    Autoplay.prototype.startAutoPlay = function () {
        var _this = this;
        this.core.outer
            .find('.lg-progress')
            .css('transition', 'width ' +
            (this.core.settings.speed +
                this.settings.slideShowInterval) +
            'ms ease 0s');
        this.core.outer.addClass('lg-show-autoplay');
        this.core.outer.find('.lg-progress-bar').addClass('lg-start');
        this.core.LGel.trigger(lg_events_1.lGEvents.autoplayStart, {
            index: this.core.index,
        });
        this.interval = setInterval(function () {
            if (_this.core.index + 1 < _this.core.galleryItems.length) {
                _this.core.index++;
            }
            else {
                _this.core.index = 0;
            }
            _this.core.LGel.trigger(lg_events_1.lGEvents.autoplay, {
                index: _this.core.index,
            });
            _this.fromAuto = true;
            _this.core.slide(_this.core.index, false, false, 'next');
        }, this.core.settings.speed + this.settings.slideShowInterval);
    };
    // cancel Autostart
    Autoplay.prototype.stopAutoPlay = function () {
        if (this.interval) {
            this.core.LGel.trigger(lg_events_1.lGEvents.autoplayStop, {
                index: this.core.index,
            });
            this.core.outer.find('.lg-progress').removeAttr('style');
            this.core.outer.removeClass('lg-show-autoplay');
            this.core.outer.find('.lg-progress-bar').removeClass('lg-start');
        }
        clearInterval(this.interval);
        this.interval = false;
    };
    Autoplay.prototype.closeGallery = function () {
        this.stopAutoPlay();
    };
    Autoplay.prototype.destroy = function () {
        if (this.settings.autoplay) {
            this.core.outer.find('.lg-progress-bar').remove();
        }
        // Remove all event listeners added by autoplay plugin
        this.core.LGel.off('.lg.autoplay');
        this.core.LGel.off('.autoplay');
    };
    return Autoplay;
}());
exports.default = Autoplay;
//# sourceMappingURL=lg-autoplay.js.map
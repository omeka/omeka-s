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
var lg_fullscreen_settings_1 = require("./lg-fullscreen-settings");
var FullScreen = /** @class */ (function () {
    function FullScreen(instance, $LG) {
        // get lightGallery core plugin instance
        this.core = instance;
        this.$LG = $LG;
        // extend module default settings with lightGallery core settings
        this.settings = __assign(__assign({}, lg_fullscreen_settings_1.fullscreenSettings), this.core.settings);
        return this;
    }
    FullScreen.prototype.init = function () {
        var fullScreen = '';
        if (this.settings.fullScreen) {
            // check for fullscreen browser support
            if (!document.fullscreenEnabled &&
                !document.webkitFullscreenEnabled &&
                !document.mozFullScreenEnabled &&
                !document.msFullscreenEnabled) {
                return;
            }
            else {
                fullScreen = "<button type=\"button\" aria-label=\"" + this.settings.fullscreenPluginStrings['toggleFullscreen'] + "\" class=\"lg-fullscreen lg-icon\"></button>";
                this.core.$toolbar.append(fullScreen);
                this.fullScreen();
            }
        }
    };
    FullScreen.prototype.isFullScreen = function () {
        return (document.fullscreenElement ||
            document.mozFullScreenElement ||
            document.webkitFullscreenElement ||
            document.msFullscreenElement);
    };
    FullScreen.prototype.requestFullscreen = function () {
        var el = document.documentElement;
        if (el.requestFullscreen) {
            el.requestFullscreen();
        }
        else if (el.msRequestFullscreen) {
            el.msRequestFullscreen();
        }
        else if (el.mozRequestFullScreen) {
            el.mozRequestFullScreen();
        }
        else if (el.webkitRequestFullscreen) {
            el.webkitRequestFullscreen();
        }
    };
    FullScreen.prototype.exitFullscreen = function () {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        }
        else if (document.msExitFullscreen) {
            document.msExitFullscreen();
        }
        else if (document.mozCancelFullScreen) {
            document.mozCancelFullScreen();
        }
        else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        }
    };
    // https://developer.mozilla.org/en-US/docs/Web/Guide/API/DOM/Using_full_screen_mode
    FullScreen.prototype.fullScreen = function () {
        var _this = this;
        this.$LG(document).on("fullscreenchange.lg.global" + this.core.lgId + " \n            webkitfullscreenchange.lg.global" + this.core.lgId + " \n            mozfullscreenchange.lg.global" + this.core.lgId + " \n            MSFullscreenChange.lg.global" + this.core.lgId, function () {
            if (!_this.core.lgOpened)
                return;
            _this.core.outer.toggleClass('lg-fullscreen-on');
        });
        this.core.outer
            .find('.lg-fullscreen')
            .first()
            .on('click.lg', function () {
            if (_this.isFullScreen()) {
                _this.exitFullscreen();
            }
            else {
                _this.requestFullscreen();
            }
        });
    };
    FullScreen.prototype.closeGallery = function () {
        // exit from fullscreen if activated
        if (this.isFullScreen()) {
            this.exitFullscreen();
        }
    };
    FullScreen.prototype.destroy = function () {
        this.$LG(document).off("fullscreenchange.lg.global" + this.core.lgId + " \n            webkitfullscreenchange.lg.global" + this.core.lgId + " \n            mozfullscreenchange.lg.global" + this.core.lgId + " \n            MSFullscreenChange.lg.global" + this.core.lgId);
    };
    return FullScreen;
}());
exports.default = FullScreen;
//# sourceMappingURL=lg-fullscreen.js.map
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
var __spreadArrays = (this && this.__spreadArrays) || function () {
    for (var s = 0, i = 0, il = arguments.length; i < il; i++) s += arguments[i].length;
    for (var r = Array(s), k = 0, i = 0; i < il; i++)
        for (var a = arguments[i], j = 0, jl = a.length; j < jl; j++, k++)
            r[k] = a[j];
    return r;
};
Object.defineProperty(exports, "__esModule", { value: true });
var lg_share_settings_1 = require("./lg-share-settings");
var lg_fb_share_utils_1 = require("./lg-fb-share-utils");
var lg_twitter_share_utils_1 = require("./lg-twitter-share-utils");
var lg_pinterest_share_utils_1 = require("./lg-pinterest-share-utils");
var lg_events_1 = require("../../lg-events");
var Share = /** @class */ (function () {
    function Share(instance) {
        this.shareOptions = [];
        // get lightGallery core plugin instance
        this.core = instance;
        // extend module default settings with lightGallery core settings
        this.settings = __assign(__assign({}, lg_share_settings_1.shareSettings), this.core.settings);
        return this;
    }
    Share.prototype.init = function () {
        if (!this.settings.share) {
            return;
        }
        this.shareOptions = __spreadArrays(this.getDefaultShareOptions(), this.settings.additionalShareOptions);
        this.setLgShareMarkup();
        this.core.outer
            .find('.lg-share .lg-dropdown')
            .append(this.getShareListHtml());
        this.core.LGel.on(lg_events_1.lGEvents.afterSlide + ".share", this.onAfterSlide.bind(this));
    };
    Share.prototype.getShareListHtml = function () {
        var shareHtml = '';
        this.shareOptions.forEach(function (shareOption) {
            shareHtml += shareOption.dropdownHTML;
        });
        return shareHtml;
    };
    Share.prototype.setLgShareMarkup = function () {
        var _this = this;
        this.core.$toolbar.append("<button type=\"button\" aria-label=\"" + this.settings.sharePluginStrings['share'] + "\" aria-haspopup=\"true\" aria-expanded=\"false\" class=\"lg-share lg-icon\">\n                <ul class=\"lg-dropdown\" style=\"position: absolute;\"></ul></button>");
        this.core.outer.append('<div class="lg-dropdown-overlay"></div>');
        var $shareButton = this.core.outer.find('.lg-share');
        $shareButton.first().on('click.lg', function () {
            _this.core.outer.toggleClass('lg-dropdown-active');
            if (_this.core.outer.hasClass('lg-dropdown-active')) {
                _this.core.outer.attr('aria-expanded', true);
            }
            else {
                _this.core.outer.attr('aria-expanded', false);
            }
        });
        this.core.outer
            .find('.lg-dropdown-overlay')
            .first()
            .on('click.lg', function () {
            _this.core.outer.removeClass('lg-dropdown-active');
            _this.core.outer.attr('aria-expanded', false);
        });
    };
    Share.prototype.onAfterSlide = function (event) {
        var _this = this;
        var index = event.detail.index;
        var currentItem = this.core.galleryItems[index];
        setTimeout(function () {
            _this.shareOptions.forEach(function (shareOption) {
                var selector = shareOption.selector;
                _this.core.outer
                    .find(selector)
                    .attr('href', shareOption.generateLink(currentItem));
            });
        }, 100);
    };
    Share.prototype.getShareListItemHTML = function (type, text) {
        return "<li><a class=\"lg-share-" + type + "\" rel=\"noopener\" target=\"_blank\"><span class=\"lg-icon\"></span><span class=\"lg-dropdown-text\">" + text + "</span></a></li>";
    };
    Share.prototype.getDefaultShareOptions = function () {
        return __spreadArrays((this.settings.facebook
            ? [
                {
                    type: 'facebook',
                    generateLink: lg_fb_share_utils_1.getFacebookShareLink,
                    dropdownHTML: this.getShareListItemHTML('facebook', this.settings.facebookDropdownText),
                    selector: '.lg-share-facebook',
                },
            ]
            : []), (this.settings.twitter
            ? [
                {
                    type: 'twitter',
                    generateLink: lg_twitter_share_utils_1.getTwitterShareLink,
                    dropdownHTML: this.getShareListItemHTML('twitter', this.settings.twitterDropdownText),
                    selector: '.lg-share-twitter',
                },
            ]
            : []), (this.settings.pinterest
            ? [
                {
                    type: 'pinterest',
                    generateLink: lg_pinterest_share_utils_1.getPinterestShareLink,
                    dropdownHTML: this.getShareListItemHTML('pinterest', this.settings.pinterestDropdownText),
                    selector: '.lg-share-pinterest',
                },
            ]
            : []));
    };
    Share.prototype.destroy = function () {
        this.core.outer.find('.lg-dropdown-overlay').remove();
        this.core.outer.find('.lg-share').remove();
        this.core.LGel.off('.lg.share');
        this.core.LGel.off('.share');
    };
    return Share;
}());
exports.default = Share;
//# sourceMappingURL=lg-share.js.map
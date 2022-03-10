"use strict";
/**
 * lightGallery comments module
 * Supports facebook and disqus comments
 *
 * @ref - https://help.disqus.com/customer/portal/articles/472098-javascript-configuration-variables
 * @ref - https://github.com/disqus/DISQUS-API-Recipes/blob/master/snippets/js/disqus-reset/disqus_reset.html
 * @ref - https://css-tricks.com/lazy-loading-disqus-comments/
 * @ref - https://developers.facebook.com/docs/plugins/comments/#comments-plugin
 *
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
var lg_comment_settings_1 = require("./lg-comment-settings");
var CommentBox = /** @class */ (function () {
    function CommentBox(instance, $LG) {
        // get lightGallery core plugin instance
        this.core = instance;
        this.$LG = $LG;
        // extend module default settings with lightGallery core settings
        this.settings = __assign(__assign({}, lg_comment_settings_1.commentSettings), this.core.settings);
        return this;
    }
    CommentBox.prototype.init = function () {
        if (!this.settings.commentBox) {
            return;
        }
        this.setMarkup();
        this.toggleCommentBox();
        if (this.settings.fbComments) {
            this.addFbComments();
        }
        else if (this.settings.disqusComments) {
            this.addDisqusComments();
        }
    };
    CommentBox.prototype.setMarkup = function () {
        this.core.outer.append(this.settings.commentsMarkup +
            '<div class="lg-comment-overlay"></div>');
        var commentToggleBtn = "<button type=\"button\" aria-label=\"" + this.settings.commentPluginStrings['toggleComments'] + "\" class=\"lg-comment-toggle lg-icon\"></button>";
        this.core.$toolbar.append(commentToggleBtn);
    };
    CommentBox.prototype.toggleCommentBox = function () {
        var _this_1 = this;
        this.core.outer
            .find('.lg-comment-toggle')
            .first()
            .on('click.lg.comment', function () {
            _this_1.core.outer.toggleClass('lg-comment-active');
        });
        this.core.outer
            .find('.lg-comment-overlay')
            .first()
            .on('click.lg.comment', function () {
            _this_1.core.outer.removeClass('lg-comment-active');
        });
        this.core.outer
            .find('.lg-comment-close')
            .first()
            .on('click.lg.comment', function () {
            _this_1.core.outer.removeClass('lg-comment-active');
        });
    };
    CommentBox.prototype.addFbComments = function () {
        var _this_1 = this;
        // eslint-disable-next-line @typescript-eslint/no-this-alias
        var _this = this;
        this.core.LGel.on(lg_events_1.lGEvents.beforeSlide + ".comment", function (event) {
            var html = _this_1.core.galleryItems[event.detail.index].fbHtml;
            _this_1.core.outer.find('.lg-comment-body').html(html);
        });
        this.core.LGel.on(lg_events_1.lGEvents.afterSlide + ".comment", function () {
            try {
                FB.XFBML.parse();
            }
            catch (err) {
                _this.$LG(window).on('fbAsyncInit', function () {
                    FB.XFBML.parse();
                });
            }
        });
    };
    CommentBox.prototype.addDisqusComments = function () {
        var _this_1 = this;
        // eslint-disable-next-line @typescript-eslint/no-this-alias
        var _this = this;
        var $disqusThread = this.$LG('#disqus_thread');
        $disqusThread.remove();
        this.core.outer
            .find('.lg-comment-body')
            .append('<div id="disqus_thread"></div>');
        this.core.LGel.on(lg_events_1.lGEvents.beforeSlide + ".comment", function () {
            $disqusThread.html('');
        });
        this.core.LGel.on(lg_events_1.lGEvents.afterSlide + ".comment", function (event) {
            var index = event.detail.index;
            // eslint-disable-next-line @typescript-eslint/no-this-alias
            var _this = _this_1;
            // DISQUS needs sometime to intialize when lightGallery is opened from direct url(hash plugin).
            setTimeout(function () {
                try {
                    DISQUS.reset({
                        reload: true,
                        config: function () {
                            this.page.identifier =
                                _this.core.galleryItems[index].disqusIdentifier;
                            this.page.url =
                                _this.core.galleryItems[index].disqusURL;
                            this.page.title =
                                _this.settings.disqusConfig.title;
                            this.language =
                                _this.settings.disqusConfig.language;
                        },
                    });
                }
                catch (err) {
                    console.error('Make sure you have included disqus JavaScript code in your document. Ex - https://lg-disqus.disqus.com/admin/install/platforms/universalcode/');
                }
            }, _this.core.lGalleryOn ? 0 : 1000);
        });
    };
    CommentBox.prototype.destroy = function () {
        this.core.LGel.off('.lg.comment');
        this.core.LGel.off('.comment');
    };
    return CommentBox;
}());
exports.default = CommentBox;
//# sourceMappingURL=lg-comment.js.map
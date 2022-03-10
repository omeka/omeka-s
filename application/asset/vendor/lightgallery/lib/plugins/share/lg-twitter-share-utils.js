"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.getTwitterShareLink = void 0;
function getTwitterShareLink(galleryItem) {
    var twitterBaseUrl = '//twitter.com/intent/tweet?text=';
    var url = encodeURIComponent(galleryItem.twitterShareUrl || window.location.href);
    var text = galleryItem.tweetText;
    return twitterBaseUrl + text + '&url=' + url;
}
exports.getTwitterShareLink = getTwitterShareLink;
//# sourceMappingURL=lg-twitter-share-utils.js.map
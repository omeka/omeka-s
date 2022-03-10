"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.getFacebookShareLink = void 0;
function getFacebookShareLink(galleryItem) {
    var facebookBaseUrl = '//www.facebook.com/sharer/sharer.php?u=';
    return (facebookBaseUrl +
        encodeURIComponent(galleryItem.facebookShareUrl || window.location.href));
}
exports.getFacebookShareLink = getFacebookShareLink;
//# sourceMappingURL=lg-fb-share-utils.js.map
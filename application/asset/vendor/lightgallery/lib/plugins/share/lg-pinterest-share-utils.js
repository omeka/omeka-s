"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.getPinterestShareLink = void 0;
function getPinterestShareLink(galleryItem) {
    var pinterestBaseUrl = 'http://www.pinterest.com/pin/create/button/?url=';
    var description = galleryItem.pinterestText;
    var media = encodeURIComponent(galleryItem.src);
    var url = encodeURIComponent(galleryItem.pinterestShareUrl || window.location.href);
    return (pinterestBaseUrl +
        url +
        '&media=' +
        media +
        '&description=' +
        description);
}
exports.getPinterestShareLink = getPinterestShareLink;
//# sourceMappingURL=lg-pinterest-share-utils.js.map
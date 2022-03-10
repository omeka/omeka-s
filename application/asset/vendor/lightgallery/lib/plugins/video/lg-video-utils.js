"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.getVimeoURLParams = exports.param = void 0;
exports.param = function (obj) {
    return Object.keys(obj)
        .map(function (k) {
        return encodeURIComponent(k) + '=' + encodeURIComponent(obj[k]);
    })
        .join('&');
};
exports.getVimeoURLParams = function (defaultParams, videoInfo) {
    if (!videoInfo || !videoInfo.vimeo)
        return '';
    var urlParams = videoInfo.vimeo[2] || '';
    urlParams =
        urlParams[0] == '?' ? '&' + urlParams.slice(1) : urlParams || '';
    var defaultPlayerParams = defaultParams
        ? '&' + exports.param(defaultParams)
        : '';
    // For vimeo last parms gets priority if duplicates found
    var vimeoPlayerParams = "?autoplay=0&muted=1" + defaultPlayerParams + urlParams;
    return vimeoPlayerParams;
};
//# sourceMappingURL=lg-video-utils.js.map
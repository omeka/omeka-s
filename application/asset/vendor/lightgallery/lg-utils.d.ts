import { lgQuery } from './lgQuery';
import { VideoSource } from './plugins/video/types';
import { VideoInfo } from './types';
export interface ImageSize {
    width: number;
    height: number;
}
export interface ImageSources {
    media?: string;
    srcset: string;
    sizes?: string;
    type?: string;
}
export interface GalleryItem {
    /**
     * url of the media
     * @data-attr data-src
     */
    src?: string;
    /**
     * Source attributes for the <a href="https://developer.mozilla.org/en-US/docs/Web/HTML/Element/source#attributes">picture</a> element
     * @data-attr data-sources
     */
    sources?: ImageSources[];
    /**
     * Thumbnail url
     * @description By default lightGallery uses the image inside gallery selector as thumbnail.
     * But, If you want to use external image for thumbnail,
     * pass the thumbnail url via any data attribute and
     * pass the attribute name via exThumbImage option
     * @example
     * <div id="lightGallery">
     *     <a href="a.jpg" data-external-thumb-image="images/externalThumb.jpg" ><img src="thumb.jpg" /></a>
     * </div>
     *
     * lightGallery(document.getElementById('lightGallery'), {
     *     exThumbImage: 'data-external-thumb-image'
     * })
     * @data-attr data-*
     */
    thumb?: string;
    /**
     * alt attribute for the image
     * @data-attr alt
     */
    alt?: string;
    /**
     * Title attribute for the video
     * @data-attr title
     */
    title?: string;
    /**
     * Title for iframe
     * @data-attr data-iframe-title
     */
    iframeTitle?: string;
    /**
     * Caption for the slide
     * @description You can either pass the HTML markup or the ID or class name of the element which contains the captions
     * @data-attr data-sub-html
     */
    subHtml?: string;
    /**
     * url of the file which contain the sub html.
     * @description Note - Does not support Internet Explorer browser
     * @data-attr data-sub-html-url
     */
    subHtmlUrl?: string;
    /**
     * Video source
     * @data-attr data-video
     */
    video?: VideoSource;
    /**
     * Poster url
     * @data-attr data-poster
     */
    poster?: string;
    /**
     * Custom slide name to use in the url when hash plugin is enabled
     * @data-attr data-slide-name
     */
    slideName?: string;
    /**
     * List of images and viewport's max width separated by comma.
     * @description Ex?: img/1-375.jpg 375, img/1-480.jpg 480, img/1-757.jpg 757.
     * @data-attr data-responsive
     */
    responsive?: string;
    /**
     * srcset attribute values for the main image
     * @data-attr data-srcset
     */
    srcset?: string;
    /**
     * srcset sizes attribute for the main image
     * @data-attr data-sizes
     */
    sizes?: string;
    /**
     * Set true is you want to open your url in an iframe
     * @data-attr data-iframe
     */
    iframe?: boolean;
    /**
     * Download url for your image/video.
     * @description Pass false if you want to disable the download button.
     * @data-attr data-download-url
     */
    downloadUrl?: string | boolean;
    /**
     * Name of the file after it is downloaded.
     * @description The HTML value of the download attribute.
     * There are no restrictions on allowed values, and the browser will automatically
     * detect the correct file extension and add it to the file (.img, .pdf, .txt, .html, etc.).
     * <a href="https://developer.mozilla.org/en-US/docs/Web/HTML/Element/a#attr-download">More info</a>
     * @data-attr data-download
     */
    download?: string | boolean;
    /**
     * Actual size of the image in px.
     * @description This is used in zoom plugin to see the actual size of the image when double taped on the image.
     * @data-attr data-width
     */
    width?: string;
    /**
     * Facebook share URL.
     * @description Specify only if you want to provide separate share URL for the specific slide. By default, current browser URL is taken.
     * @data-attr data-facebook-share-url
     */
    facebookShareUrl?: string;
    /**
     * Tweet text
     * @data-attr data-tweet-text
     */
    tweetText?: string;
    /**
     * Twitter share URL.
     * @description Specify only if you want to provide separate share URL for the specific slide. By default, current browser URL will be taken.
     * @data-attr data-twitter-share-url
     */
    twitterShareUrl?: string;
    /**
     * Pinterest share URL.
     * @description Specify only if you want to provide separate share URL for the specific slide. By default, current browser URL will be taken.
     * Note?: Pinterest requires absolute URL
     * @data-attr data-pinterest-share-url
     */
    pinterestShareUrl?: string;
    /**
     * Description for Pinterest post.
     * @data-attr data-pinterest-text
     */
    pinterestText?: string;
    /**
     * Facebook comments body html
     * @description Please refer <a href="https://developers.facebook.com/docs/plugins/comments/#comments-plugin">facebook official documentation</a> for generating the HTML markup
     * @example
     * <div
     *      class="fb-comments"
     *      data-href="https://www.lightgalleryjs.com/demos/comment-box/#facebook-comments-demo"
     *      data-width="400"
     *      data-numposts="5">
     * </div>
     * @data-attr data-fb-html
     */
    fbHtml?: string;
    /**
     * Disqus page identifier
     * @description Please refer official <a href="https://help.disqus.com/en/articles/1717084-javascript-configuration-variables">disqus documentation</a> for more info
     * @data-attr data-disqus-identifier
     */
    disqusIdentifier?: string;
    /**
     * Disqus page url
     * @description Please refer official <a href="https://help.disqus.com/en/articles/1717084-javascript-configuration-variables">disqus documentation</a> for more info
     * @data-attr data-disqus-url
     */
    disqusUrl?: string;
    __slideVideoInfo?: VideoInfo;
    [key: string]: any;
}
export declare function convertToData(attr: string): string;
declare const utils: {
    /**
     * get possible width and height from the lgSize attribute. Used for ZoomFromOrigin option
     */
    getSize(el: HTMLElement, container: lgQuery, spacing?: number, defaultLgSize?: string | undefined): ImageSize | undefined;
    /**
     * @desc Get transform value based on the imageSize. Used for ZoomFromOrigin option
     * @param {jQuery Element}
     * @returns {String} Transform CSS string
     */
    getTransform(el: HTMLElement, container: lgQuery, top: number, bottom: number, imageSize?: ImageSize | undefined): string | undefined;
    getIframeMarkup(iframeWidth: string, iframeHeight: string, iframeMaxWidth: string, iframeMaxHeight: string, src?: string | undefined, iframeTitle?: string | undefined): string;
    getImgMarkup(index: number, src: string, altAttr: string, srcset?: string | undefined, sizes?: string | undefined, sources?: ImageSources[] | undefined): string;
    getResponsiveSrc(srcItms: string[]): string;
    isImageLoaded(img: HTMLImageElement): boolean;
    getVideoPosterMarkup(_poster: string, dummyImg: string, videoContStyle: string, playVideoString: string, _isVideo?: VideoInfo | undefined): string;
    /**
     * @desc Create dynamic elements array from gallery items when dynamic option is false
     * It helps to avoid frequent DOM interaction
     * and avoid multiple checks for dynamic elments
     *
     * @returns {Array} dynamicEl
     */
    getDynamicOptions(items: any, extraProps: string[], getCaptionFromTitleOrAlt: boolean, exThumbImage: string): GalleryItem[];
    isMobile(): boolean;
    /**
     * @desc Check the given src is video
     * @param {String} src
     * @return {Object} video type
     * Ex:{ youtube  :  ["//www.youtube.com/watch?v=c0asJgSyxcY", "c0asJgSyxcY"] }
     *
     * @todo - this information can be moved to dynamicEl to avoid frequent calls
     */
    isVideo(src: string, isHTML5VIdeo: boolean, index: number): VideoInfo | undefined;
};
export default utils;

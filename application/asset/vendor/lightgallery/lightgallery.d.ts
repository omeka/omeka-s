import { LightGalleryAllSettings, LightGallerySettings } from './lg-settings';
import { GalleryItem, ImageSize } from './lg-utils';
import { lgQuery } from './lgQuery';
import { Coords, MediaContainerPosition, SlideDirection } from './types';
export declare class LightGallery {
    settings: LightGalleryAllSettings;
    galleryItems: GalleryItem[];
    lgId: number;
    el: HTMLElement;
    LGel: lgQuery;
    lgOpened: boolean;
    index: number;
    plugins: any[];
    lGalleryOn: boolean;
    lgBusy: boolean;
    touchAction?: 'swipe' | 'zoomSwipe' | 'pinch';
    swipeDirection?: 'horizontal' | 'vertical';
    hideBarTimeout: any;
    currentItemsInDom: string[];
    outer: lgQuery;
    items: any;
    $backdrop: lgQuery;
    $lgComponents: lgQuery;
    $container: lgQuery;
    $inner: lgQuery;
    $content: lgQuery;
    $toolbar: lgQuery;
    prevScrollTop: number;
    private zoomFromOrigin;
    private currentImageSize?;
    private isDummyImageRemoved;
    private dragOrSwipeEnabled;
    mediaContainerPosition: {
        top: number;
        bottom: number;
    };
    constructor(element: HTMLElement, options?: LightGallerySettings);
    private generateSettings;
    private normalizeSettings;
    init(): void;
    openGalleryOnItemClick(): void;
    /**
     * Module constructor
     * Modules are build incrementally.
     * Gallery should be opened only once all the modules are initialized.
     * use moduleBuildTimeout to make sure this
     */
    buildModules(): void;
    validateLicense(): void;
    getSlideItem(index: number): lgQuery;
    getSlideItemId(index: number): string;
    getIdName(id: string): string;
    getElementById(id: string): lgQuery;
    manageSingleSlideClassName(): void;
    buildStructure(): void;
    refreshOnResize(): void;
    resizeVideoSlide(index: number, imageSize?: ImageSize): void;
    /**
     * Update slides dynamically.
     * Add, edit or delete slides dynamically when lightGallery is opened.
     * Modify the current gallery items and pass it via updateSlides method
     * @note
     * - Do not mutate existing lightGallery items directly.
     * - Always pass new list of gallery items
     * - You need to take care of thumbnails outside the gallery if any
     * - user this method only if you want to update slides when the gallery is opened. Otherwise, use `refresh()` method.
     * @param items Gallery items
     * @param index After the update operation, which slide gallery should navigate to
     * @category lGPublicMethods
     * @example
     * const plugin = lightGallery();
     *
     * // Adding slides dynamically
     * let galleryItems = [
     * // Access existing lightGallery items
     * // galleryItems are automatically generated internally from the gallery HTML markup
     * // or directly from galleryItems when dynamic gallery is used
     *   ...plugin.galleryItems,
     *     ...[
     *       {
     *         src: 'img/img-1.png',
     *           thumb: 'img/thumb1.png',
     *         },
     *     ],
     *   ];
     *   plugin.updateSlides(
     *     galleryItems,
     *     plugin.index,
     *   );
     *
     *
     * // Remove slides dynamically
     * galleryItems = JSON.parse(
     *   JSON.stringify(updateSlideInstance.galleryItems),
     * );
     * galleryItems.shift();
     * updateSlideInstance.updateSlides(galleryItems, 1);
     * @see <a href="/demos/update-slides/">Demo</a>
     */
    updateSlides(items: GalleryItem[], index: number): void;
    getItems(): GalleryItem[];
    /**
     * Open lightGallery.
     * Open gallery with specific slide by passing index of the slide as parameter.
     * @category lGPublicMethods
     * @param {Number} index  - index of the slide
     * @param {HTMLElement} element - Which image lightGallery should zoom from
     *
     * @example
     * const $dynamicGallery = document.getElementById('dynamic-gallery-demo');
     * const dynamicGallery = lightGallery($dynamicGallery, {
     *     dynamic: true,
     *     dynamicEl: [
     *         {
     *              src: 'img/1.jpg',
     *              thumb: 'img/thumb-1.jpg',
     *              subHtml: '<h4>Image 1 title</h4><p>Image 1 descriptions.</p>',
     *         },
     *         ...
     *     ],
     * });
     * $dynamicGallery.addEventListener('click', function () {
     *     // Starts with third item.(Optional).
     *     // This is useful if you want use dynamic mode with
     *     // custom thumbnails (thumbnails outside gallery),
     *     dynamicGallery.openGallery(2);
     * });
     *
     */
    openGallery(index?: number, element?: HTMLElement): void;
    /**
     * Note - Changing the position of the media on every slide transition creates a flickering effect.
     * Therefore, The height of the caption is calculated dynamically, only once based on the first slide caption.
     * if you have dynamic captions for each media,
     * you can provide an appropriate height for the captions via allowMediaOverlap option
     */
    getMediaContainerPosition(): MediaContainerPosition;
    private setMediaContainerPosition;
    hideBars(): void;
    initPictureFill($img: lgQuery): void;
    /**
     *  @desc Create image counter
     *  Ex: 1/10
     */
    counter(): void;
    /**
     *  @desc add sub-html into the slide
     *  @param {Number} index - index of the slide
     */
    addHtml(index: number): void;
    /**
     *  @desc Preload slides
     *  @param {Number} index - index of the slide
     * @todo preload not working for the first slide, Also, should work for the first and last slide as well
     */
    preload(index: number): void;
    getDummyImgStyles(imageSize?: ImageSize): string;
    getVideoContStyle(imageSize?: ImageSize): string;
    getDummyImageContent($currentSlide: lgQuery, index: number, alt: string): string;
    setImgMarkup(src: string, $currentSlide: lgQuery, index: number): void;
    onSlideObjectLoad($slide: lgQuery, isHTML5VideoWithoutPoster: boolean, onLoad: () => void, onError: () => void): void;
    /**
     *
     * @param $el Current slide item
     * @param index
     * @param delay Delay is 0 except first time
     * @param speed Speed is same as delay, except it is 0 if gallery is opened via hash plugin
     * @param isFirstSlide
     */
    onLgObjectLoad(currentSlide: lgQuery, index: number, delay: number, speed: number, isFirstSlide: boolean, isHTML5VideoWithoutPoster: boolean): void;
    triggerSlideItemLoad($currentSlide: lgQuery, index: number, delay: number, speed: number, isFirstSlide: boolean): void;
    isFirstSlideWithZoomAnimation(): boolean;
    addSlideVideoInfo(items: GalleryItem[]): void;
    /**
     *  Load slide content into slide.
     *  This is used to load content into slides that is not visible too
     *  @param {Number} index - index of the slide.
     *  @param {Boolean} rec - if true call loadcontent() function again.
     */
    loadContent(index: number, rec: boolean): void;
    /**
     * @desc Remove dummy image content and load next slides
     * Called only for the first time if zoomFromOrigin animation is enabled
     * @param index
     * @param $currentSlide
     * @param speed
     */
    loadContentOnFirstSlideLoad(index: number, $currentSlide: lgQuery, speed: number): void;
    getItemsToBeInsertedToDom(index: number, prevIndex: number, numberOfItems?: number): string[];
    organizeSlideItems(index: number, prevIndex: number): string[];
    /**
     * Get previous index of the slide
     */
    getPreviousSlideIndex(): number;
    setDownloadValue(index: number): void;
    makeSlideAnimation(direction: 'next' | 'prev', currentSlideItem: lgQuery, previousSlideItem: lgQuery): void;
    /**
     * Goto a specific slide.
     * @param {Number} index - index of the slide
     * @param {Boolean} fromTouch - true if slide function called via touch event or mouse drag
     * @param {Boolean} fromThumb - true if slide function called via thumbnail click
     * @param {String} direction - Direction of the slide(next/prev)
     * @category lGPublicMethods
     * @example
     *  const plugin = lightGallery();
     *  // to go to 3rd slide
     *  plugin.slide(2);
     *
     */
    slide(index: number, fromTouch?: boolean, fromThumb?: boolean, direction?: SlideDirection | false): void;
    updateCurrentCounter(index: number): void;
    updateCounterTotal(): void;
    getSlideType(item: GalleryItem): 'video' | 'iframe' | 'image';
    touchMove(startCoords: Coords, endCoords: Coords, e?: TouchEvent): void;
    touchEnd(endCoords: Coords, startCoords: Coords, event: TouchEvent): void;
    enableSwipe(): void;
    enableDrag(): void;
    triggerPosterClick(): void;
    manageSwipeClass(): void;
    /**
     * Go to next slide
     * @param {Boolean} fromTouch - true if slide function called via touch event
     * @category lGPublicMethods
     * @example
     *  const plugin = lightGallery();
     *  plugin.goToNextSlide();
     * @see <a href="/demos/methods/">Demo</a>
     */
    goToNextSlide(fromTouch?: boolean): void;
    /**
     * Go to previous slides
     * @param {Boolean} fromTouch - true if slide function called via touch event
     * @category lGPublicMethods
     * @example
     *  const plugin = lightGallery({});
     *  plugin.goToPrevSlide();
     * @see <a href="/demos/methods/">Demo</a>
     *
     */
    goToPrevSlide(fromTouch?: boolean): void;
    keyPress(): void;
    arrow(): void;
    arrowDisable(index: number): void;
    setTranslate($el: lgQuery, xValue: number, yValue: number, scaleX?: number, scaleY?: number): void;
    mousewheel(): void;
    isSlideElement(target: lgQuery): boolean;
    isPosterElement(target: lgQuery): boolean;
    /**
     * Maximize minimize inline gallery.
     * @category lGPublicMethods
     */
    toggleMaximize(): void;
    invalidateItems(): void;
    manageCloseGallery(): void;
    /**
     * Close lightGallery if it is opened.
     *
     * @description If closable is false in the settings, you need to pass true via closeGallery method to force close gallery
     * @return returns the estimated time to close gallery completely including the close animation duration
     * @category lGPublicMethods
     * @example
     *  const plugin = lightGallery();
     *  plugin.closeGallery();
     *
     */
    closeGallery(force?: boolean): number;
    initModules(): void;
    destroyModules(destroy?: true): void;
    /**
     * Refresh lightGallery with new set of children.
     *
     * @description This is useful to update the gallery when the child elements are changed without calling destroy method.
     *
     * If you are using dynamic mode, you can pass the modified array of dynamicEl as the first parameter to refresh the dynamic gallery
     * @see <a href="/demos/dynamic-mode/">Demo</a>
     * @category lGPublicMethods
     * @example
     *  const plugin = lightGallery();
     *  // Delete or add children, then call
     *  plugin.refresh();
     *
     */
    refresh(galleryItems?: GalleryItem[]): void;
    updateControls(): void;
    /**
     * Destroy lightGallery.
     * Destroy lightGallery and its plugin instances completely
     *
     * @description This method also calls CloseGallery function internally. Returns the time takes to completely close and destroy the instance.
     * In case if you want to re-initialize lightGallery right after destroying it, initialize it only once the destroy process is completed.
     * You can use refresh method most of the times.
     * @category lGPublicMethods
     * @example
     *  const plugin = lightGallery();
     *  plugin.destroy();
     *
     */
    destroy(): number;
}

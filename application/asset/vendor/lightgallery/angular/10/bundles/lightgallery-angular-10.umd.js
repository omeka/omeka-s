(function (global, factory) {
    typeof exports === 'object' && typeof module !== 'undefined' ? factory(exports, require('@angular/core'), require('lightgallery')) :
    typeof define === 'function' && define.amd ? define('lightgallery/angular/10', ['exports', '@angular/core', 'lightgallery'], factory) :
    (global = typeof globalThis !== 'undefined' ? globalThis : global || self, factory((global.lightgallery = global.lightgallery || {}, global.lightgallery.angular = global.lightgallery.angular || {}, global.lightgallery.angular['10'] = {}), global.ng.core, global.lightGallery));
}(this, (function (exports, i0, lightGallery) { 'use strict';

    function _interopDefaultLegacy (e) { return e && typeof e === 'object' && 'default' in e ? e : { 'default': e }; }

    var lightGallery__default = /*#__PURE__*/_interopDefaultLegacy(lightGallery);

    var LightgalleryAngualr9Service = /** @class */ (function () {
        function LightgalleryAngualr9Service() {
        }
        return LightgalleryAngualr9Service;
    }());
    LightgalleryAngualr9Service.ɵfac = function LightgalleryAngualr9Service_Factory(t) { return new (t || LightgalleryAngualr9Service)(); };
    LightgalleryAngualr9Service.ɵprov = i0.ɵɵdefineInjectable({ token: LightgalleryAngualr9Service, factory: LightgalleryAngualr9Service.ɵfac, providedIn: 'root' });
    /*@__PURE__*/ (function () {
        i0.ɵsetClassMetadata(LightgalleryAngualr9Service, [{
                type: i0.Injectable,
                args: [{
                        providedIn: 'root',
                    }]
            }], function () { return []; }, null);
    })();

    var _c0 = ["*"];
    var LgMethods = {
        onAfterAppendSlide: 'lgAfterAppendSlide',
        onInit: 'lgInit',
        onHasVideo: 'lgHasVideo',
        onContainerResize: 'lgContainerResize',
        onUpdateSlides: 'lgUpdateSlides',
        onAfterAppendSubHtml: 'lgAfterAppendSubHtml',
        onBeforeOpen: 'lgBeforeOpen',
        onAfterOpen: 'lgAfterOpen',
        onSlideItemLoad: 'lgSlideItemLoad',
        onBeforeSlide: 'lgBeforeSlide',
        onAfterSlide: 'lgAfterSlide',
        onPosterClick: 'lgPosterClick',
        onDragStart: 'lgDragStart',
        onDragMove: 'lgDragMove',
        onDragEnd: 'lgDragEnd',
        onBeforeNextSlide: 'lgBeforeNextSlide',
        onBeforePrevSlide: 'lgBeforePrevSlide',
        onBeforeClose: 'lgBeforeClose',
        onAfterClose: 'lgAfterClose',
        onRotateLeft: 'lgRotateLeft',
        onRotateRight: 'lgRotateRight',
        onFlipHorizontal: 'lgFlipHorizontal',
        onFlipVertical: 'lgFlipVertical',
    };
    var LightgalleryComponent = /** @class */ (function () {
        function LightgalleryComponent(_elementRef) {
            this._elementRef = _elementRef;
            this.lgInitialized = false;
            this._elementRef = _elementRef;
        }
        LightgalleryComponent.prototype.ngAfterViewChecked = function () {
            if (!this.lgInitialized) {
                this.registerEvents();
                this.LG = lightGallery__default['default'](this._elementRef.nativeElement, this.settings);
                this.lgInitialized = true;
            }
        };
        LightgalleryComponent.prototype.ngOnDestroy = function () {
            this.LG.destroy();
            this.lgInitialized = false;
        };
        LightgalleryComponent.prototype.registerEvents = function () {
            var _this = this;
            if (this.onAfterAppendSlide) {
                this._elementRef.nativeElement.addEventListener(LgMethods.onAfterAppendSlide, (function (event) {
                    _this.onAfterAppendSlide &&
                        _this.onAfterAppendSlide(event.detail);
                }));
            }
            if (this.onInit) {
                this._elementRef.nativeElement.addEventListener(LgMethods.onInit, (function (event) {
                    _this.onInit && _this.onInit(event.detail);
                }));
            }
            if (this.onHasVideo) {
                this._elementRef.nativeElement.addEventListener(LgMethods.onHasVideo, (function (event) {
                    _this.onHasVideo && _this.onHasVideo(event.detail);
                }));
            }
            if (this.onContainerResize) {
                this._elementRef.nativeElement.addEventListener(LgMethods.onContainerResize, (function (event) {
                    _this.onContainerResize &&
                        _this.onContainerResize(event.detail);
                }));
            }
            if (this.onAfterAppendSubHtml) {
                this._elementRef.nativeElement.addEventListener(LgMethods.onAfterAppendSubHtml, (function (event) {
                    _this.onAfterAppendSubHtml &&
                        _this.onAfterAppendSubHtml(event.detail);
                }));
            }
            if (this.onBeforeOpen) {
                this._elementRef.nativeElement.addEventListener(LgMethods.onBeforeOpen, (function (event) {
                    _this.onBeforeOpen && _this.onBeforeOpen(event.detail);
                }));
            }
            if (this.onAfterOpen) {
                this._elementRef.nativeElement.addEventListener(LgMethods.onAfterOpen, (function (event) {
                    _this.onAfterOpen && _this.onAfterOpen(event.detail);
                }));
            }
            if (this.onSlideItemLoad) {
                this._elementRef.nativeElement.addEventListener(LgMethods.onSlideItemLoad, (function (event) {
                    _this.onSlideItemLoad && _this.onSlideItemLoad(event.detail);
                }));
            }
            if (this.onBeforeSlide) {
                this._elementRef.nativeElement.addEventListener(LgMethods.onBeforeSlide, (function (event) {
                    _this.onBeforeSlide && _this.onBeforeSlide(event.detail);
                }));
            }
            if (this.onAfterSlide) {
                this._elementRef.nativeElement.addEventListener(LgMethods.onAfterSlide, (function (event) {
                    _this.onAfterSlide && _this.onAfterSlide(event.detail);
                }));
            }
            if (this.onPosterClick) {
                this._elementRef.nativeElement.addEventListener(LgMethods.onPosterClick, (function (event) {
                    _this.onPosterClick && _this.onPosterClick(event.detail);
                }));
            }
            if (this.onDragStart) {
                this._elementRef.nativeElement.addEventListener(LgMethods.onDragStart, (function (event) {
                    _this.onDragStart && _this.onDragStart(event.detail);
                }));
            }
            if (this.onDragMove) {
                this._elementRef.nativeElement.addEventListener(LgMethods.onDragMove, (function (event) {
                    _this.onDragMove && _this.onDragMove(event.detail);
                }));
            }
            if (this.onDragEnd) {
                this._elementRef.nativeElement.addEventListener(LgMethods.onDragEnd, (function (event) {
                    _this.onDragEnd && _this.onDragEnd(event.detail);
                }));
            }
            if (this.onBeforeNextSlide) {
                this._elementRef.nativeElement.addEventListener(LgMethods.onBeforeNextSlide, (function (event) {
                    _this.onBeforeNextSlide &&
                        _this.onBeforeNextSlide(event.detail);
                }));
            }
            if (this.onBeforePrevSlide) {
                this._elementRef.nativeElement.addEventListener(LgMethods.onBeforePrevSlide, (function (event) {
                    _this.onBeforePrevSlide &&
                        _this.onBeforePrevSlide(event.detail);
                }));
            }
            if (this.onBeforeClose) {
                this._elementRef.nativeElement.addEventListener(LgMethods.onBeforeClose, (function (event) {
                    _this.onBeforeClose && _this.onBeforeClose(event.detail);
                }));
            }
            if (this.onAfterClose) {
                this._elementRef.nativeElement.addEventListener(LgMethods.onAfterClose, (function (event) {
                    _this.onAfterClose && _this.onAfterClose(event.detail);
                }));
            }
            if (this.onRotateLeft) {
                this._elementRef.nativeElement.addEventListener(LgMethods.onRotateLeft, (function (event) {
                    _this.onRotateLeft && _this.onRotateLeft(event.detail);
                }));
            }
            if (this.onRotateRight) {
                this._elementRef.nativeElement.addEventListener(LgMethods.onRotateRight, (function (event) {
                    _this.onRotateRight && _this.onRotateRight(event.detail);
                }));
            }
            if (this.onFlipHorizontal) {
                this._elementRef.nativeElement.addEventListener(LgMethods.onFlipHorizontal, (function (event) {
                    _this.onFlipHorizontal &&
                        _this.onFlipHorizontal(event.detail);
                }));
            }
            if (this.onFlipVertical) {
                this._elementRef.nativeElement.addEventListener(LgMethods.onFlipVertical, (function (event) {
                    _this.onFlipVertical && _this.onFlipVertical(event.detail);
                }));
            }
        };
        return LightgalleryComponent;
    }());
    LightgalleryComponent.ɵfac = function LightgalleryComponent_Factory(t) { return new (t || LightgalleryComponent)(i0.ɵɵdirectiveInject(i0.ElementRef)); };
    LightgalleryComponent.ɵcmp = i0.ɵɵdefineComponent({ type: LightgalleryComponent, selectors: [["lightgallery"]], inputs: { settings: "settings", onAfterAppendSlide: "onAfterAppendSlide", onInit: "onInit", onHasVideo: "onHasVideo", onContainerResize: "onContainerResize", onAfterAppendSubHtml: "onAfterAppendSubHtml", onBeforeOpen: "onBeforeOpen", onAfterOpen: "onAfterOpen", onSlideItemLoad: "onSlideItemLoad", onBeforeSlide: "onBeforeSlide", onAfterSlide: "onAfterSlide", onPosterClick: "onPosterClick", onDragStart: "onDragStart", onDragMove: "onDragMove", onDragEnd: "onDragEnd", onBeforeNextSlide: "onBeforeNextSlide", onBeforePrevSlide: "onBeforePrevSlide", onBeforeClose: "onBeforeClose", onAfterClose: "onAfterClose", onRotateLeft: "onRotateLeft", onRotateRight: "onRotateRight", onFlipHorizontal: "onFlipHorizontal", onFlipVertical: "onFlipVertical" }, ngContentSelectors: _c0, decls: 1, vars: 0, template: function LightgalleryComponent_Template(rf, ctx) {
            if (rf & 1) {
                i0.ɵɵprojectionDef();
                i0.ɵɵprojection(0);
            }
        }, encapsulation: 2 });
    /*@__PURE__*/ (function () {
        i0.ɵsetClassMetadata(LightgalleryComponent, [{
                type: i0.Component,
                args: [{
                        selector: 'lightgallery',
                        template: '<ng-content></ng-content>',
                        styles: [],
                    }]
            }], function () { return [{ type: i0.ElementRef }]; }, { settings: [{
                    type: i0.Input
                }], onAfterAppendSlide: [{
                    type: i0.Input
                }], onInit: [{
                    type: i0.Input
                }], onHasVideo: [{
                    type: i0.Input
                }], onContainerResize: [{
                    type: i0.Input
                }], onAfterAppendSubHtml: [{
                    type: i0.Input
                }], onBeforeOpen: [{
                    type: i0.Input
                }], onAfterOpen: [{
                    type: i0.Input
                }], onSlideItemLoad: [{
                    type: i0.Input
                }], onBeforeSlide: [{
                    type: i0.Input
                }], onAfterSlide: [{
                    type: i0.Input
                }], onPosterClick: [{
                    type: i0.Input
                }], onDragStart: [{
                    type: i0.Input
                }], onDragMove: [{
                    type: i0.Input
                }], onDragEnd: [{
                    type: i0.Input
                }], onBeforeNextSlide: [{
                    type: i0.Input
                }], onBeforePrevSlide: [{
                    type: i0.Input
                }], onBeforeClose: [{
                    type: i0.Input
                }], onAfterClose: [{
                    type: i0.Input
                }], onRotateLeft: [{
                    type: i0.Input
                }], onRotateRight: [{
                    type: i0.Input
                }], onFlipHorizontal: [{
                    type: i0.Input
                }], onFlipVertical: [{
                    type: i0.Input
                }] });
    })();

    var LightgalleryModule = /** @class */ (function () {
        function LightgalleryModule() {
        }
        return LightgalleryModule;
    }());
    LightgalleryModule.ɵmod = i0.ɵɵdefineNgModule({ type: LightgalleryModule });
    LightgalleryModule.ɵinj = i0.ɵɵdefineInjector({ factory: function LightgalleryModule_Factory(t) { return new (t || LightgalleryModule)(); }, imports: [[]] });
    (function () { (typeof ngJitMode === "undefined" || ngJitMode) && i0.ɵɵsetNgModuleScope(LightgalleryModule, { declarations: [LightgalleryComponent], exports: [LightgalleryComponent] }); })();
    /*@__PURE__*/ (function () {
        i0.ɵsetClassMetadata(LightgalleryModule, [{
                type: i0.NgModule,
                args: [{
                        declarations: [LightgalleryComponent],
                        imports: [],
                        exports: [LightgalleryComponent],
                    }]
            }], null, null);
    })();

    /*
     * Public API Surface of lightgallery-angular9
     */

    /**
     * Generated bundle index. Do not edit.
     */

    exports.LightgalleryAngualr9Service = LightgalleryAngualr9Service;
    exports.LightgalleryComponent = LightgalleryComponent;
    exports.LightgalleryModule = LightgalleryModule;

    Object.defineProperty(exports, '__esModule', { value: true });

})));
//# sourceMappingURL=lightgallery-angular-10.umd.js.map

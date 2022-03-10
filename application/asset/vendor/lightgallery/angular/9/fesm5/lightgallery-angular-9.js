import { ɵɵdefineInjectable, ɵsetClassMetadata, Injectable, ɵɵdirectiveInject, ElementRef, ɵɵdefineComponent, ɵɵprojectionDef, ɵɵprojection, Component, Input, ɵɵdefineNgModule, ɵɵdefineInjector, ɵɵsetNgModuleScope, NgModule } from '@angular/core';
import lightGallery from 'lightgallery';

var LightgalleryAngualr9Service = /** @class */ (function () {
    function LightgalleryAngualr9Service() {
    }
    LightgalleryAngualr9Service.ɵfac = function LightgalleryAngualr9Service_Factory(t) { return new (t || LightgalleryAngualr9Service)(); };
    LightgalleryAngualr9Service.ɵprov = ɵɵdefineInjectable({ token: LightgalleryAngualr9Service, factory: LightgalleryAngualr9Service.ɵfac, providedIn: 'root' });
    return LightgalleryAngualr9Service;
}());
/*@__PURE__*/ (function () { ɵsetClassMetadata(LightgalleryAngualr9Service, [{
        type: Injectable,
        args: [{
                providedIn: 'root',
            }]
    }], function () { return []; }, null); })();

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
            this.LG = lightGallery(this._elementRef.nativeElement, this.settings);
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
    LightgalleryComponent.ɵfac = function LightgalleryComponent_Factory(t) { return new (t || LightgalleryComponent)(ɵɵdirectiveInject(ElementRef)); };
    LightgalleryComponent.ɵcmp = ɵɵdefineComponent({ type: LightgalleryComponent, selectors: [["lightgallery"]], inputs: { settings: "settings", onAfterAppendSlide: "onAfterAppendSlide", onInit: "onInit", onHasVideo: "onHasVideo", onContainerResize: "onContainerResize", onAfterAppendSubHtml: "onAfterAppendSubHtml", onBeforeOpen: "onBeforeOpen", onAfterOpen: "onAfterOpen", onSlideItemLoad: "onSlideItemLoad", onBeforeSlide: "onBeforeSlide", onAfterSlide: "onAfterSlide", onPosterClick: "onPosterClick", onDragStart: "onDragStart", onDragMove: "onDragMove", onDragEnd: "onDragEnd", onBeforeNextSlide: "onBeforeNextSlide", onBeforePrevSlide: "onBeforePrevSlide", onBeforeClose: "onBeforeClose", onAfterClose: "onAfterClose", onRotateLeft: "onRotateLeft", onRotateRight: "onRotateRight", onFlipHorizontal: "onFlipHorizontal", onFlipVertical: "onFlipVertical" }, ngContentSelectors: _c0, decls: 1, vars: 0, template: function LightgalleryComponent_Template(rf, ctx) { if (rf & 1) {
            ɵɵprojectionDef();
            ɵɵprojection(0);
        } }, encapsulation: 2 });
    return LightgalleryComponent;
}());
/*@__PURE__*/ (function () { ɵsetClassMetadata(LightgalleryComponent, [{
        type: Component,
        args: [{
                selector: 'lightgallery',
                template: '<ng-content></ng-content>',
                styles: [],
            }]
    }], function () { return [{ type: ElementRef }]; }, { settings: [{
            type: Input
        }], onAfterAppendSlide: [{
            type: Input
        }], onInit: [{
            type: Input
        }], onHasVideo: [{
            type: Input
        }], onContainerResize: [{
            type: Input
        }], onAfterAppendSubHtml: [{
            type: Input
        }], onBeforeOpen: [{
            type: Input
        }], onAfterOpen: [{
            type: Input
        }], onSlideItemLoad: [{
            type: Input
        }], onBeforeSlide: [{
            type: Input
        }], onAfterSlide: [{
            type: Input
        }], onPosterClick: [{
            type: Input
        }], onDragStart: [{
            type: Input
        }], onDragMove: [{
            type: Input
        }], onDragEnd: [{
            type: Input
        }], onBeforeNextSlide: [{
            type: Input
        }], onBeforePrevSlide: [{
            type: Input
        }], onBeforeClose: [{
            type: Input
        }], onAfterClose: [{
            type: Input
        }], onRotateLeft: [{
            type: Input
        }], onRotateRight: [{
            type: Input
        }], onFlipHorizontal: [{
            type: Input
        }], onFlipVertical: [{
            type: Input
        }] }); })();

var LightgalleryModule = /** @class */ (function () {
    function LightgalleryModule() {
    }
    LightgalleryModule.ɵmod = ɵɵdefineNgModule({ type: LightgalleryModule });
    LightgalleryModule.ɵinj = ɵɵdefineInjector({ factory: function LightgalleryModule_Factory(t) { return new (t || LightgalleryModule)(); }, imports: [[]] });
    return LightgalleryModule;
}());
(function () { (typeof ngJitMode === "undefined" || ngJitMode) && ɵɵsetNgModuleScope(LightgalleryModule, { declarations: [LightgalleryComponent], exports: [LightgalleryComponent] }); })();
/*@__PURE__*/ (function () { ɵsetClassMetadata(LightgalleryModule, [{
        type: NgModule,
        args: [{
                declarations: [LightgalleryComponent],
                imports: [],
                exports: [LightgalleryComponent],
            }]
    }], null, null); })();

/*
 * Public API Surface of lightgallery-angular9
 */

/**
 * Generated bundle index. Do not edit.
 */

export { LightgalleryAngualr9Service, LightgalleryComponent, LightgalleryModule };
//# sourceMappingURL=lightgallery-angular-9.js.map

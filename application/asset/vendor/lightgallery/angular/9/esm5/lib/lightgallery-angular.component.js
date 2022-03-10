import { Component, Input } from '@angular/core';
import lightGallery from 'lightgallery';
import * as i0 from "@angular/core";
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
    LightgalleryComponent.ɵfac = function LightgalleryComponent_Factory(t) { return new (t || LightgalleryComponent)(i0.ɵɵdirectiveInject(i0.ElementRef)); };
    LightgalleryComponent.ɵcmp = i0.ɵɵdefineComponent({ type: LightgalleryComponent, selectors: [["lightgallery"]], inputs: { settings: "settings", onAfterAppendSlide: "onAfterAppendSlide", onInit: "onInit", onHasVideo: "onHasVideo", onContainerResize: "onContainerResize", onAfterAppendSubHtml: "onAfterAppendSubHtml", onBeforeOpen: "onBeforeOpen", onAfterOpen: "onAfterOpen", onSlideItemLoad: "onSlideItemLoad", onBeforeSlide: "onBeforeSlide", onAfterSlide: "onAfterSlide", onPosterClick: "onPosterClick", onDragStart: "onDragStart", onDragMove: "onDragMove", onDragEnd: "onDragEnd", onBeforeNextSlide: "onBeforeNextSlide", onBeforePrevSlide: "onBeforePrevSlide", onBeforeClose: "onBeforeClose", onAfterClose: "onAfterClose", onRotateLeft: "onRotateLeft", onRotateRight: "onRotateRight", onFlipHorizontal: "onFlipHorizontal", onFlipVertical: "onFlipVertical" }, ngContentSelectors: _c0, decls: 1, vars: 0, template: function LightgalleryComponent_Template(rf, ctx) { if (rf & 1) {
            i0.ɵɵprojectionDef();
            i0.ɵɵprojection(0);
        } }, encapsulation: 2 });
    return LightgalleryComponent;
}());
export { LightgalleryComponent };
/*@__PURE__*/ (function () { i0.ɵsetClassMetadata(LightgalleryComponent, [{
        type: Component,
        args: [{
                selector: 'lightgallery',
                template: '<ng-content></ng-content>',
                styles: [],
            }]
    }], function () { return [{ type: i0.ElementRef }]; }, { settings: [{
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
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoibGlnaHRnYWxsZXJ5LWFuZ3VsYXIuY29tcG9uZW50LmpzIiwic291cmNlUm9vdCI6Im5nOi8vbGlnaHRnYWxsZXJ5L2FuZ3VsYXIvOS8iLCJzb3VyY2VzIjpbImxpYi9saWdodGdhbGxlcnktYW5ndWxhci5jb21wb25lbnQudHMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6IkFBQUEsT0FBTyxFQUFFLFNBQVMsRUFBYyxLQUFLLEVBQUUsTUFBTSxlQUFlLENBQUM7QUFDN0QsT0FBTyxZQUFZLE1BQU0sY0FBYyxDQUFDOzs7QUEwQnhDLElBQU0sU0FBUyxHQUFHO0lBQ2Qsa0JBQWtCLEVBQUUsb0JBQW9CO0lBQ3hDLE1BQU0sRUFBRSxRQUFRO0lBQ2hCLFVBQVUsRUFBRSxZQUFZO0lBQ3hCLGlCQUFpQixFQUFFLG1CQUFtQjtJQUN0QyxjQUFjLEVBQUUsZ0JBQWdCO0lBQ2hDLG9CQUFvQixFQUFFLHNCQUFzQjtJQUM1QyxZQUFZLEVBQUUsY0FBYztJQUM1QixXQUFXLEVBQUUsYUFBYTtJQUMxQixlQUFlLEVBQUUsaUJBQWlCO0lBQ2xDLGFBQWEsRUFBRSxlQUFlO0lBQzlCLFlBQVksRUFBRSxjQUFjO0lBQzVCLGFBQWEsRUFBRSxlQUFlO0lBQzlCLFdBQVcsRUFBRSxhQUFhO0lBQzFCLFVBQVUsRUFBRSxZQUFZO0lBQ3hCLFNBQVMsRUFBRSxXQUFXO0lBQ3RCLGlCQUFpQixFQUFFLG1CQUFtQjtJQUN0QyxpQkFBaUIsRUFBRSxtQkFBbUI7SUFDdEMsYUFBYSxFQUFFLGVBQWU7SUFDOUIsWUFBWSxFQUFFLGNBQWM7SUFDNUIsWUFBWSxFQUFFLGNBQWM7SUFDNUIsYUFBYSxFQUFFLGVBQWU7SUFDOUIsZ0JBQWdCLEVBQUUsa0JBQWtCO0lBQ3BDLGNBQWMsRUFBRSxnQkFBZ0I7Q0FDbkMsQ0FBQztBQUVGO0lBUUksK0JBQW9CLFdBQXVCO1FBQXZCLGdCQUFXLEdBQVgsV0FBVyxDQUFZO1FBRG5DLGtCQUFhLEdBQUcsS0FBSyxDQUFDO1FBRTFCLElBQUksQ0FBQyxXQUFX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QjtrREFBckIscUJBQXFCO2NBTGpDLFNBQVM7ZUFBQztnQkFDUCxRQUFRLEVBQUUsY0FBYztnQkFDeEIsUUFBUSxFQUFFLDJCQUEyQjtnQkFDckMsTUFBTSxFQUFFLEVBQUU7YUFDYjs7a0JBUUksS0FBSzs7a0JBQ0wsS0FBSzs7a0JBQ0wsS0FBSzs7a0JBQ0wsS0FBSzs7a0JBQ0wsS0FBSzs7a0JBQ0wsS0FBSzs7a0JBQ0wsS0FBSzs7a0JBQ0wsS0FBSzs7a0JBQ0wsS0FBSzs7a0JBQ0wsS0FBSzs7a0JBQ0wsS0FBSzs7a0JBQ0wsS0FBSzs7a0JBQ0wsS0FBSzs7a0JBQ0wsS0FBSzs7a0JBQ0wsS0FBSzs7a0JBQ0wsS0FBSzs7a0JBQ0wsS0FBSzs7a0JBQ0wsS0FBSzs7a0JBQ0wsS0FBSzs7a0JBQ0wsS0FBSzs7a0JBQ0wsS0FBSzs7a0JBQ0wsS0FBSzs7a0JBQ0wsS0FBSyIsInNvdXJjZXNDb250ZW50IjpbImltcG9ydCB7IENvbXBvbmVudCwgRWxlbWVudFJlZiwgSW5wdXQgfSBmcm9tICdAYW5ndWxhci9jb3JlJztcbmltcG9ydCBsaWdodEdhbGxlcnkgZnJvbSAnbGlnaHRnYWxsZXJ5JztcbmltcG9ydCB7XG4gICAgQWZ0ZXJBcHBlbmRTdWJIdG1sRGV0YWlsLFxuICAgIEFmdGVyQ2xvc2VEZXRhaWwsXG4gICAgQWZ0ZXJPcGVuRGV0YWlsLFxuICAgIEFmdGVyU2xpZGVEZXRhaWwsXG4gICAgQmVmb3JlQ2xvc2VEZXRhaWwsXG4gICAgQmVmb3JlTmV4dFNsaWRlRGV0YWlsLFxuICAgIEJlZm9yZU9wZW5EZXRhaWwsXG4gICAgQmVmb3JlUHJldlNsaWRlRGV0YWlsLFxuICAgIEJlZm9yZVNsaWRlRGV0YWlsLFxuICAgIENvbnRhaW5lclJlc2l6ZURldGFpbCxcbiAgICBEcmFnRW5kRGV0YWlsLFxuICAgIERyYWdNb3ZlRGV0YWlsLFxuICAgIERyYWdTdGFydERldGFpbCxcbiAgICBGbGlwSG9yaXpvbnRhbERldGFpbCxcbiAgICBGbGlwVmVydGljYWxEZXRhaWwsXG4gICAgSW5pdERldGFpbCxcbiAgICBQb3N0ZXJDbGlja0RldGFpbCxcbiAgICBSb3RhdGVMZWZ0RGV0YWlsLFxuICAgIFJvdGF0ZVJpZ2h0RGV0YWlsLFxuICAgIFNsaWRlSXRlbUxvYWREZXRhaWwsXG59IGZyb20gJ2xpZ2h0Z2FsbGVyeS9sZy1ldmVudHMnO1xuaW1wb3J0IHsgTGlnaHRHYWxsZXJ5U2V0dGluZ3MgfSBmcm9tICdsaWdodGdhbGxlcnkvbGctc2V0dGluZ3MnO1xuaW1wb3J0IHsgTGlnaHRHYWxsZXJ5IH0gZnJvbSAnbGlnaHRnYWxsZXJ5L2xpZ2h0Z2FsbGVyeSc7XG5cbmNvbnN0IExnTWV0aG9kcyA9IHtcbiAgICBvbkFmdGVyQXBwZW5kU2xpZGU6ICdsZ0FmdGVyQXBwZW5kU2xpZGUnLFxuICAgIG9uSW5pdDogJ2xnSW5pdCcsXG4gICAgb25IYXNWaWRlbzogJ2xnSGFzVmlkZW8nLFxuICAgIG9uQ29udGFpbmVyUmVzaXplOiAnbGdDb250YWluZXJSZXNpemUnLFxuICAgIG9uVXBkYXRlU2xpZGVzOiAnbGdVcGRhdGVTbGlkZXMnLFxuICAgIG9uQWZ0ZXJBcHBlbmRTdWJIdG1sOiAnbGdBZnRlckFwcGVuZFN1Ykh0bWwnLFxuICAgIG9uQmVmb3JlT3BlbjogJ2xnQmVmb3JlT3BlbicsXG4gICAgb25BZnRlck9wZW46ICdsZ0FmdGVyT3BlbicsXG4gICAgb25TbGlkZUl0ZW1Mb2FkOiAnbGdTbGlkZUl0ZW1Mb2FkJyxcbiAgICBvbkJlZm9yZVNsaWRlOiAnbGdCZWZvcmVTbGlkZScsXG4gICAgb25BZnRlclNsaWRlOiAnbGdBZnRlclNsaWRlJyxcbiAgICBvblBvc3RlckNsaWNrOiAnbGdQb3N0ZXJDbGljaycsXG4gICAgb25EcmFnU3RhcnQ6ICdsZ0RyYWdTdGFydCcsXG4gICAgb25EcmFnTW92ZTogJ2xnRHJhZ01vdmUnLFxuICAgIG9uRHJhZ0VuZDogJ2xnRHJhZ0VuZCcsXG4gICAgb25CZWZvcmVOZXh0U2xpZGU6ICdsZ0JlZm9yZU5leHRTbGlkZScsXG4gICAgb25CZWZvcmVQcmV2U2xpZGU6ICdsZ0JlZm9yZVByZXZTbGlkZScsXG4gICAgb25CZWZvcmVDbG9zZTogJ2xnQmVmb3JlQ2xvc2UnLFxuICAgIG9uQWZ0ZXJDbG9zZTogJ2xnQWZ0ZXJDbG9zZScsXG4gICAgb25Sb3RhdGVMZWZ0OiAnbGdSb3RhdGVMZWZ0JyxcbiAgICBvblJvdGF0ZVJpZ2h0OiAnbGdSb3RhdGVSaWdodCcsXG4gICAgb25GbGlwSG9yaXpvbnRhbDogJ2xnRmxpcEhvcml6b250YWwnLFxuICAgIG9uRmxpcFZlcnRpY2FsOiAnbGdGbGlwVmVydGljYWwnLFxufTtcblxuQENvbXBvbmVudCh7XG4gICAgc2VsZWN0b3I6ICdsaWdodGdhbGxlcnknLFxuICAgIHRlbXBsYXRlOiAnPG5nLWNvbnRlbnQ+PC9uZy1jb250ZW50PicsXG4gICAgc3R5bGVzOiBbXSxcbn0pXG5leHBvcnQgY2xhc3MgTGlnaHRnYWxsZXJ5Q29tcG9uZW50IHtcbiAgICBwcml2YXRlIExHITogTGlnaHRHYWxsZXJ5O1xuICAgIHByaXZhdGUgbGdJbml0aWFsaXplZCA9IGZhbHNlO1xuICAgIGNvbnN0cnVjdG9yKHByaXZhdGUgX2VsZW1lbnRSZWY6IEVsZW1lbnRSZWYpIHtcbiAgICAgICAgdGhpcy5fZWxlbWVudFJlZiA9IF9lbGVtZW50UmVmO1xuICAgIH1cblxuICAgIEBJbnB1dCgpIHNldHRpbmdzITogTGlnaHRHYWxsZXJ5U2V0dGluZ3M7XG4gICAgQElucHV0KCkgb25BZnRlckFwcGVuZFNsaWRlPzogKGRldGFpbDogQWZ0ZXJTbGlkZURldGFpbCkgPT4gdm9pZDtcbiAgICBASW5wdXQoKSBvbkluaXQ/OiAoZGV0YWlsOiBJbml0RGV0YWlsKSA9PiB2b2lkO1xuICAgIEBJbnB1dCgpIG9uSGFzVmlkZW8/OiAoZGV0YWlsOiBJbml0RGV0YWlsKSA9PiB2b2lkO1xuICAgIEBJbnB1dCgpIG9uQ29udGFpbmVyUmVzaXplPzogKGRldGFpbDogQ29udGFpbmVyUmVzaXplRGV0YWlsKSA9PiB2b2lkO1xuICAgIEBJbnB1dCgpIG9uQWZ0ZXJBcHBlbmRTdWJIdG1sPzogKGRldGFpbDogQWZ0ZXJBcHBlbmRTdWJIdG1sRGV0YWlsKSA9PiB2b2lkO1xuICAgIEBJbnB1dCgpIG9uQmVmb3JlT3Blbj86IChkZXRhaWw6IEJlZm9yZU9wZW5EZXRhaWwpID0+IHZvaWQ7XG4gICAgQElucHV0KCkgb25BZnRlck9wZW4/OiAoZGV0YWlsOiBBZnRlck9wZW5EZXRhaWwpID0+IHZvaWQ7XG4gICAgQElucHV0KCkgb25TbGlkZUl0ZW1Mb2FkPzogKGRldGFpbDogU2xpZGVJdGVtTG9hZERldGFpbCkgPT4gdm9pZDtcbiAgICBASW5wdXQoKSBvbkJlZm9yZVNsaWRlPzogKGRldGFpbDogQmVmb3JlU2xpZGVEZXRhaWwpID0+IHZvaWQ7XG4gICAgQElucHV0KCkgb25BZnRlclNsaWRlPzogKGRldGFpbDogQWZ0ZXJTbGlkZURldGFpbCkgPT4gdm9pZDtcbiAgICBASW5wdXQoKSBvblBvc3RlckNsaWNrPzogKGRldGFpbDogUG9zdGVyQ2xpY2tEZXRhaWwpID0+IHZvaWQ7XG4gICAgQElucHV0KCkgb25EcmFnU3RhcnQ/OiAoZGV0YWlsOiBEcmFnU3RhcnREZXRhaWwpID0+IHZvaWQ7XG4gICAgQElucHV0KCkgb25EcmFnTW92ZT86IChkZXRhaWw6IERyYWdNb3ZlRGV0YWlsKSA9PiB2b2lkO1xuICAgIEBJbnB1dCgpIG9uRHJhZ0VuZD86IChkZXRhaWw6IERyYWdFbmREZXRhaWwpID0+IHZvaWQ7XG4gICAgQElucHV0KCkgb25CZWZvcmVOZXh0U2xpZGU/OiAoZGV0YWlsOiBCZWZvcmVOZXh0U2xpZGVEZXRhaWwpID0+IHZvaWQ7XG4gICAgQElucHV0KCkgb25CZWZvcmVQcmV2U2xpZGU/OiAoZGV0YWlsOiBCZWZvcmVQcmV2U2xpZGVEZXRhaWwpID0+IHZvaWQ7XG4gICAgQElucHV0KCkgb25CZWZvcmVDbG9zZT86IChkZXRhaWw6IEJlZm9yZUNsb3NlRGV0YWlsKSA9PiB2b2lkO1xuICAgIEBJbnB1dCgpIG9uQWZ0ZXJDbG9zZT86IChkZXRhaWw6IEFmdGVyQ2xvc2VEZXRhaWwpID0+IHZvaWQ7XG4gICAgQElucHV0KCkgb25Sb3RhdGVMZWZ0PzogKGRldGFpbDogUm90YXRlTGVmdERldGFpbCkgPT4gdm9pZDtcbiAgICBASW5wdXQoKSBvblJvdGF0ZVJpZ2h0PzogKGRldGFpbDogUm90YXRlUmlnaHREZXRhaWwpID0+IHZvaWQ7XG4gICAgQElucHV0KCkgb25GbGlwSG9yaXpvbnRhbD86IChkZXRhaWw6IEZsaXBIb3Jpem9udGFsRGV0YWlsKSA9PiB2b2lkO1xuICAgIEBJbnB1dCgpIG9uRmxpcFZlcnRpY2FsPzogKGRldGFpbDogRmxpcFZlcnRpY2FsRGV0YWlsKSA9PiB2b2lkO1xuXG4gICAgbmdBZnRlclZpZXdDaGVja2VkKCk6IHZvaWQge1xuICAgICAgICBpZiAoIXRoaXMubGdJbml0aWFsaXplZCkge1xuICAgICAgICAgICAgdGhpcy5yZWdpc3RlckV2ZW50cygpO1xuICAgICAgICAgICAgdGhpcy5MRyA9IGxpZ2h0R2FsbGVyeShcbiAgICAgICAgICAgICAgICB0aGlzLl9lbGVtZW50UmVmLm5hdGl2ZUVsZW1lbnQgYXMgSFRNTEVsZW1lbnQsXG4gICAgICAgICAgICAgICAgdGhpcy5zZXR0aW5ncyxcbiAgICAgICAgICAgICk7XG4gICAgICAgICAgICB0aGlzLmxnSW5pdGlhbGl6ZWQgPSB0cnVlO1xuICAgICAgICB9XG4gICAgfVxuXG4gICAgbmdPbkRlc3Ryb3koKTogdm9pZCB7XG4gICAgICAgIHRoaXMuTEcuZGVzdHJveSgpO1xuICAgICAgICB0aGlzLmxnSW5pdGlhbGl6ZWQgPSBmYWxzZTtcbiAgICB9XG5cbiAgICBwcml2YXRlIHJlZ2lzdGVyRXZlbnRzKCk6IHZvaWQge1xuICAgICAgICBpZiAodGhpcy5vbkFmdGVyQXBwZW5kU2xpZGUpIHtcbiAgICAgICAgICAgIHRoaXMuX2VsZW1lbnRSZWYubmF0aXZlRWxlbWVudC5hZGRFdmVudExpc3RlbmVyKFxuICAgICAgICAgICAgICAgIExnTWV0aG9kcy5vbkFmdGVyQXBwZW5kU2xpZGUsXG4gICAgICAgICAgICAgICAgKChldmVudDogQ3VzdG9tRXZlbnQpID0+IHtcbiAgICAgICAgICAgICAgICAgICAgdGhpcy5vbkFmdGVyQXBwZW5kU2xpZGUgJiZcbiAgICAgICAgICAgICAgICAgICAgICAgIHRoaXMub25BZnRlckFwcGVuZFNsaWRlKGV2ZW50LmRldGFpbCk7XG4gICAgICAgICAgICAgICAgfSkgYXMgRXZlbnRMaXN0ZW5lcixcbiAgICAgICAgICAgICk7XG4gICAgICAgIH1cbiAgICAgICAgaWYgKHRoaXMub25Jbml0KSB7XG4gICAgICAgICAgICB0aGlzLl9lbGVtZW50UmVmLm5hdGl2ZUVsZW1lbnQuYWRkRXZlbnRMaXN0ZW5lcihMZ01ldGhvZHMub25Jbml0LCAoKFxuICAgICAgICAgICAgICAgIGV2ZW50OiBDdXN0b21FdmVudCxcbiAgICAgICAgICAgICkgPT4ge1xuICAgICAgICAgICAgICAgIHRoaXMub25Jbml0ICYmIHRoaXMub25Jbml0KGV2ZW50LmRldGFpbCk7XG4gICAgICAgICAgICB9KSBhcyBFdmVudExpc3RlbmVyKTtcbiAgICAgICAgfVxuICAgICAgICBpZiAodGhpcy5vbkhhc1ZpZGVvKSB7XG4gICAgICAgICAgICB0aGlzLl9lbGVtZW50UmVmLm5hdGl2ZUVsZW1lbnQuYWRkRXZlbnRMaXN0ZW5lcihcbiAgICAgICAgICAgICAgICBMZ01ldGhvZHMub25IYXNWaWRlbyxcbiAgICAgICAgICAgICAgICAoKGV2ZW50OiBDdXN0b21FdmVudCkgPT4ge1xuICAgICAgICAgICAgICAgICAgICB0aGlzLm9uSGFzVmlkZW8gJiYgdGhpcy5vbkhhc1ZpZGVvKGV2ZW50LmRldGFpbCk7XG4gICAgICAgICAgICAgICAgfSkgYXMgRXZlbnRMaXN0ZW5lcixcbiAgICAgICAgICAgICk7XG4gICAgICAgIH1cbiAgICAgICAgaWYgKHRoaXMub25Db250YWluZXJSZXNpemUpIHtcbiAgICAgICAgICAgIHRoaXMuX2VsZW1lbnRSZWYubmF0aXZlRWxlbWVudC5hZGRFdmVudExpc3RlbmVyKFxuICAgICAgICAgICAgICAgIExnTWV0aG9kcy5vbkNvbnRhaW5lclJlc2l6ZSxcbiAgICAgICAgICAgICAgICAoKGV2ZW50OiBDdXN0b21FdmVudCkgPT4ge1xuICAgICAgICAgICAgICAgICAgICB0aGlzLm9uQ29udGFpbmVyUmVzaXplICYmXG4gICAgICAgICAgICAgICAgICAgICAgICB0aGlzLm9uQ29udGFpbmVyUmVzaXplKGV2ZW50LmRldGFpbCk7XG4gICAgICAgICAgICAgICAgfSkgYXMgRXZlbnRMaXN0ZW5lcixcbiAgICAgICAgICAgICk7XG4gICAgICAgIH1cbiAgICAgICAgaWYgKHRoaXMub25BZnRlckFwcGVuZFN1Ykh0bWwpIHtcbiAgICAgICAgICAgIHRoaXMuX2VsZW1lbnRSZWYubmF0aXZlRWxlbWVudC5hZGRFdmVudExpc3RlbmVyKFxuICAgICAgICAgICAgICAgIExnTWV0aG9kcy5vbkFmdGVyQXBwZW5kU3ViSHRtbCxcbiAgICAgICAgICAgICAgICAoKGV2ZW50OiBDdXN0b21FdmVudCkgPT4ge1xuICAgICAgICAgICAgICAgICAgICB0aGlzLm9uQWZ0ZXJBcHBlbmRTdWJIdG1sICYmXG4gICAgICAgICAgICAgICAgICAgICAgICB0aGlzLm9uQWZ0ZXJBcHBlbmRTdWJIdG1sKGV2ZW50LmRldGFpbCk7XG4gICAgICAgICAgICAgICAgfSkgYXMgRXZlbnRMaXN0ZW5lcixcbiAgICAgICAgICAgICk7XG4gICAgICAgIH1cbiAgICAgICAgaWYgKHRoaXMub25CZWZvcmVPcGVuKSB7XG4gICAgICAgICAgICB0aGlzLl9lbGVtZW50UmVmLm5hdGl2ZUVsZW1lbnQuYWRkRXZlbnRMaXN0ZW5lcihcbiAgICAgICAgICAgICAgICBMZ01ldGhvZHMub25CZWZvcmVPcGVuLFxuICAgICAgICAgICAgICAgICgoZXZlbnQ6IEN1c3RvbUV2ZW50KSA9PiB7XG4gICAgICAgICAgICAgICAgICAgIHRoaXMub25CZWZvcmVPcGVuICYmIHRoaXMub25CZWZvcmVPcGVuKGV2ZW50LmRldGFpbCk7XG4gICAgICAgICAgICAgICAgfSkgYXMgRXZlbnRMaXN0ZW5lcixcbiAgICAgICAgICAgICk7XG4gICAgICAgIH1cbiAgICAgICAgaWYgKHRoaXMub25BZnRlck9wZW4pIHtcbiAgICAgICAgICAgIHRoaXMuX2VsZW1lbnRSZWYubmF0aXZlRWxlbWVudC5hZGRFdmVudExpc3RlbmVyKFxuICAgICAgICAgICAgICAgIExnTWV0aG9kcy5vbkFmdGVyT3BlbixcbiAgICAgICAgICAgICAgICAoKGV2ZW50OiBDdXN0b21FdmVudCkgPT4ge1xuICAgICAgICAgICAgICAgICAgICB0aGlzLm9uQWZ0ZXJPcGVuICYmIHRoaXMub25BZnRlck9wZW4oZXZlbnQuZGV0YWlsKTtcbiAgICAgICAgICAgICAgICB9KSBhcyBFdmVudExpc3RlbmVyLFxuICAgICAgICAgICAgKTtcbiAgICAgICAgfVxuICAgICAgICBpZiAodGhpcy5vblNsaWRlSXRlbUxvYWQpIHtcbiAgICAgICAgICAgIHRoaXMuX2VsZW1lbnRSZWYubmF0aXZlRWxlbWVudC5hZGRFdmVudExpc3RlbmVyKFxuICAgICAgICAgICAgICAgIExnTWV0aG9kcy5vblNsaWRlSXRlbUxvYWQsXG4gICAgICAgICAgICAgICAgKChldmVudDogQ3VzdG9tRXZlbnQpID0+IHtcbiAgICAgICAgICAgICAgICAgICAgdGhpcy5vblNsaWRlSXRlbUxvYWQgJiYgdGhpcy5vblNsaWRlSXRlbUxvYWQoZXZlbnQuZGV0YWlsKTtcbiAgICAgICAgICAgICAgICB9KSBhcyBFdmVudExpc3RlbmVyLFxuICAgICAgICAgICAgKTtcbiAgICAgICAgfVxuICAgICAgICBpZiAodGhpcy5vbkJlZm9yZVNsaWRlKSB7XG4gICAgICAgICAgICB0aGlzLl9lbGVtZW50UmVmLm5hdGl2ZUVsZW1lbnQuYWRkRXZlbnRMaXN0ZW5lcihcbiAgICAgICAgICAgICAgICBMZ01ldGhvZHMub25CZWZvcmVTbGlkZSxcbiAgICAgICAgICAgICAgICAoKGV2ZW50OiBDdXN0b21FdmVudCkgPT4ge1xuICAgICAgICAgICAgICAgICAgICB0aGlzLm9uQmVmb3JlU2xpZGUgJiYgdGhpcy5vbkJlZm9yZVNsaWRlKGV2ZW50LmRldGFpbCk7XG4gICAgICAgICAgICAgICAgfSkgYXMgRXZlbnRMaXN0ZW5lcixcbiAgICAgICAgICAgICk7XG4gICAgICAgIH1cbiAgICAgICAgaWYgKHRoaXMub25BZnRlclNsaWRlKSB7XG4gICAgICAgICAgICB0aGlzLl9lbGVtZW50UmVmLm5hdGl2ZUVsZW1lbnQuYWRkRXZlbnRMaXN0ZW5lcihcbiAgICAgICAgICAgICAgICBMZ01ldGhvZHMub25BZnRlclNsaWRlLFxuICAgICAgICAgICAgICAgICgoZXZlbnQ6IEN1c3RvbUV2ZW50KSA9PiB7XG4gICAgICAgICAgICAgICAgICAgIHRoaXMub25BZnRlclNsaWRlICYmIHRoaXMub25BZnRlclNsaWRlKGV2ZW50LmRldGFpbCk7XG4gICAgICAgICAgICAgICAgfSkgYXMgRXZlbnRMaXN0ZW5lcixcbiAgICAgICAgICAgICk7XG4gICAgICAgIH1cbiAgICAgICAgaWYgKHRoaXMub25Qb3N0ZXJDbGljaykge1xuICAgICAgICAgICAgdGhpcy5fZWxlbWVudFJlZi5uYXRpdmVFbGVtZW50LmFkZEV2ZW50TGlzdGVuZXIoXG4gICAgICAgICAgICAgICAgTGdNZXRob2RzLm9uUG9zdGVyQ2xpY2ssXG4gICAgICAgICAgICAgICAgKChldmVudDogQ3VzdG9tRXZlbnQpID0+IHtcbiAgICAgICAgICAgICAgICAgICAgdGhpcy5vblBvc3RlckNsaWNrICYmIHRoaXMub25Qb3N0ZXJDbGljayhldmVudC5kZXRhaWwpO1xuICAgICAgICAgICAgICAgIH0pIGFzIEV2ZW50TGlzdGVuZXIsXG4gICAgICAgICAgICApO1xuICAgICAgICB9XG4gICAgICAgIGlmICh0aGlzLm9uRHJhZ1N0YXJ0KSB7XG4gICAgICAgICAgICB0aGlzLl9lbGVtZW50UmVmLm5hdGl2ZUVsZW1lbnQuYWRkRXZlbnRMaXN0ZW5lcihcbiAgICAgICAgICAgICAgICBMZ01ldGhvZHMub25EcmFnU3RhcnQsXG4gICAgICAgICAgICAgICAgKChldmVudDogQ3VzdG9tRXZlbnQpID0+IHtcbiAgICAgICAgICAgICAgICAgICAgdGhpcy5vbkRyYWdTdGFydCAmJiB0aGlzLm9uRHJhZ1N0YXJ0KGV2ZW50LmRldGFpbCk7XG4gICAgICAgICAgICAgICAgfSkgYXMgRXZlbnRMaXN0ZW5lcixcbiAgICAgICAgICAgICk7XG4gICAgICAgIH1cbiAgICAgICAgaWYgKHRoaXMub25EcmFnTW92ZSkge1xuICAgICAgICAgICAgdGhpcy5fZWxlbWVudFJlZi5uYXRpdmVFbGVtZW50LmFkZEV2ZW50TGlzdGVuZXIoXG4gICAgICAgICAgICAgICAgTGdNZXRob2RzLm9uRHJhZ01vdmUsXG4gICAgICAgICAgICAgICAgKChldmVudDogQ3VzdG9tRXZlbnQpID0+IHtcbiAgICAgICAgICAgICAgICAgICAgdGhpcy5vbkRyYWdNb3ZlICYmIHRoaXMub25EcmFnTW92ZShldmVudC5kZXRhaWwpO1xuICAgICAgICAgICAgICAgIH0pIGFzIEV2ZW50TGlzdGVuZXIsXG4gICAgICAgICAgICApO1xuICAgICAgICB9XG4gICAgICAgIGlmICh0aGlzLm9uRHJhZ0VuZCkge1xuICAgICAgICAgICAgdGhpcy5fZWxlbWVudFJlZi5uYXRpdmVFbGVtZW50LmFkZEV2ZW50TGlzdGVuZXIoXG4gICAgICAgICAgICAgICAgTGdNZXRob2RzLm9uRHJhZ0VuZCxcbiAgICAgICAgICAgICAgICAoKGV2ZW50OiBDdXN0b21FdmVudCkgPT4ge1xuICAgICAgICAgICAgICAgICAgICB0aGlzLm9uRHJhZ0VuZCAmJiB0aGlzLm9uRHJhZ0VuZChldmVudC5kZXRhaWwpO1xuICAgICAgICAgICAgICAgIH0pIGFzIEV2ZW50TGlzdGVuZXIsXG4gICAgICAgICAgICApO1xuICAgICAgICB9XG4gICAgICAgIGlmICh0aGlzLm9uQmVmb3JlTmV4dFNsaWRlKSB7XG4gICAgICAgICAgICB0aGlzLl9lbGVtZW50UmVmLm5hdGl2ZUVsZW1lbnQuYWRkRXZlbnRMaXN0ZW5lcihcbiAgICAgICAgICAgICAgICBMZ01ldGhvZHMub25CZWZvcmVOZXh0U2xpZGUsXG4gICAgICAgICAgICAgICAgKChldmVudDogQ3VzdG9tRXZlbnQpID0+IHtcbiAgICAgICAgICAgICAgICAgICAgdGhpcy5vbkJlZm9yZU5leHRTbGlkZSAmJlxuICAgICAgICAgICAgICAgICAgICAgICAgdGhpcy5vbkJlZm9yZU5leHRTbGlkZShldmVudC5kZXRhaWwpO1xuICAgICAgICAgICAgICAgIH0pIGFzIEV2ZW50TGlzdGVuZXIsXG4gICAgICAgICAgICApO1xuICAgICAgICB9XG4gICAgICAgIGlmICh0aGlzLm9uQmVmb3JlUHJldlNsaWRlKSB7XG4gICAgICAgICAgICB0aGlzLl9lbGVtZW50UmVmLm5hdGl2ZUVsZW1lbnQuYWRkRXZlbnRMaXN0ZW5lcihcbiAgICAgICAgICAgICAgICBMZ01ldGhvZHMub25CZWZvcmVQcmV2U2xpZGUsXG4gICAgICAgICAgICAgICAgKChldmVudDogQ3VzdG9tRXZlbnQpID0+IHtcbiAgICAgICAgICAgICAgICAgICAgdGhpcy5vbkJlZm9yZVByZXZTbGlkZSAmJlxuICAgICAgICAgICAgICAgICAgICAgICAgdGhpcy5vbkJlZm9yZVByZXZTbGlkZShldmVudC5kZXRhaWwpO1xuICAgICAgICAgICAgICAgIH0pIGFzIEV2ZW50TGlzdGVuZXIsXG4gICAgICAgICAgICApO1xuICAgICAgICB9XG4gICAgICAgIGlmICh0aGlzLm9uQmVmb3JlQ2xvc2UpIHtcbiAgICAgICAgICAgIHRoaXMuX2VsZW1lbnRSZWYubmF0aXZlRWxlbWVudC5hZGRFdmVudExpc3RlbmVyKFxuICAgICAgICAgICAgICAgIExnTWV0aG9kcy5vbkJlZm9yZUNsb3NlLFxuICAgICAgICAgICAgICAgICgoZXZlbnQ6IEN1c3RvbUV2ZW50KSA9PiB7XG4gICAgICAgICAgICAgICAgICAgIHRoaXMub25CZWZvcmVDbG9zZSAmJiB0aGlzLm9uQmVmb3JlQ2xvc2UoZXZlbnQuZGV0YWlsKTtcbiAgICAgICAgICAgICAgICB9KSBhcyBFdmVudExpc3RlbmVyLFxuICAgICAgICAgICAgKTtcbiAgICAgICAgfVxuICAgICAgICBpZiAodGhpcy5vbkFmdGVyQ2xvc2UpIHtcbiAgICAgICAgICAgIHRoaXMuX2VsZW1lbnRSZWYubmF0aXZlRWxlbWVudC5hZGRFdmVudExpc3RlbmVyKFxuICAgICAgICAgICAgICAgIExnTWV0aG9kcy5vbkFmdGVyQ2xvc2UsXG4gICAgICAgICAgICAgICAgKChldmVudDogQ3VzdG9tRXZlbnQpID0+IHtcbiAgICAgICAgICAgICAgICAgICAgdGhpcy5vbkFmdGVyQ2xvc2UgJiYgdGhpcy5vbkFmdGVyQ2xvc2UoZXZlbnQuZGV0YWlsKTtcbiAgICAgICAgICAgICAgICB9KSBhcyBFdmVudExpc3RlbmVyLFxuICAgICAgICAgICAgKTtcbiAgICAgICAgfVxuICAgICAgICBpZiAodGhpcy5vblJvdGF0ZUxlZnQpIHtcbiAgICAgICAgICAgIHRoaXMuX2VsZW1lbnRSZWYubmF0aXZlRWxlbWVudC5hZGRFdmVudExpc3RlbmVyKFxuICAgICAgICAgICAgICAgIExnTWV0aG9kcy5vblJvdGF0ZUxlZnQsXG4gICAgICAgICAgICAgICAgKChldmVudDogQ3VzdG9tRXZlbnQpID0+IHtcbiAgICAgICAgICAgICAgICAgICAgdGhpcy5vblJvdGF0ZUxlZnQgJiYgdGhpcy5vblJvdGF0ZUxlZnQoZXZlbnQuZGV0YWlsKTtcbiAgICAgICAgICAgICAgICB9KSBhcyBFdmVudExpc3RlbmVyLFxuICAgICAgICAgICAgKTtcbiAgICAgICAgfVxuICAgICAgICBpZiAodGhpcy5vblJvdGF0ZVJpZ2h0KSB7XG4gICAgICAgICAgICB0aGlzLl9lbGVtZW50UmVmLm5hdGl2ZUVsZW1lbnQuYWRkRXZlbnRMaXN0ZW5lcihcbiAgICAgICAgICAgICAgICBMZ01ldGhvZHMub25Sb3RhdGVSaWdodCxcbiAgICAgICAgICAgICAgICAoKGV2ZW50OiBDdXN0b21FdmVudCkgPT4ge1xuICAgICAgICAgICAgICAgICAgICB0aGlzLm9uUm90YXRlUmlnaHQgJiYgdGhpcy5vblJvdGF0ZVJpZ2h0KGV2ZW50LmRldGFpbCk7XG4gICAgICAgICAgICAgICAgfSkgYXMgRXZlbnRMaXN0ZW5lcixcbiAgICAgICAgICAgICk7XG4gICAgICAgIH1cbiAgICAgICAgaWYgKHRoaXMub25GbGlwSG9yaXpvbnRhbCkge1xuICAgICAgICAgICAgdGhpcy5fZWxlbWVudFJlZi5uYXRpdmVFbGVtZW50LmFkZEV2ZW50TGlzdGVuZXIoXG4gICAgICAgICAgICAgICAgTGdNZXRob2RzLm9uRmxpcEhvcml6b250YWwsXG4gICAgICAgICAgICAgICAgKChldmVudDogQ3VzdG9tRXZlbnQpID0+IHtcbiAgICAgICAgICAgICAgICAgICAgdGhpcy5vbkZsaXBIb3Jpem9udGFsICYmXG4gICAgICAgICAgICAgICAgICAgICAgICB0aGlzLm9uRmxpcEhvcml6b250YWwoZXZlbnQuZGV0YWlsKTtcbiAgICAgICAgICAgICAgICB9KSBhcyBFdmVudExpc3RlbmVyLFxuICAgICAgICAgICAgKTtcbiAgICAgICAgfVxuICAgICAgICBpZiAodGhpcy5vbkZsaXBWZXJ0aWNhbCkge1xuICAgICAgICAgICAgdGhpcy5fZWxlbWVudFJlZi5uYXRpdmVFbGVtZW50LmFkZEV2ZW50TGlzdGVuZXIoXG4gICAgICAgICAgICAgICAgTGdNZXRob2RzLm9uRmxpcFZlcnRpY2FsLFxuICAgICAgICAgICAgICAgICgoZXZlbnQ6IEN1c3RvbUV2ZW50KSA9PiB7XG4gICAgICAgICAgICAgICAgICAgIHRoaXMub25GbGlwVmVydGljYWwgJiYgdGhpcy5vbkZsaXBWZXJ0aWNhbChldmVudC5kZXRhaWwpO1xuICAgICAgICAgICAgICAgIH0pIGFzIEV2ZW50TGlzdGVuZXIsXG4gICAgICAgICAgICApO1xuICAgICAgICB9XG4gICAgfVxufVxuIl19
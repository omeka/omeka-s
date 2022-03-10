import { Vue } from 'vue-class-component';
import { LightGallery as LGPlugin } from '../lightgallery';
import { LightGallerySettings } from '../lg-settings';
import { AfterAppendSubHtmlDetail, AfterCloseDetail, RotateLeftDetail, RotateRightDetail, FlipHorizontalDetail, FlipVerticalDetail, AfterOpenDetail, AfterSlideDetail, BeforeCloseDetail, BeforeNextSlideDetail, BeforeOpenDetail, BeforePrevSlideDetail, BeforeSlideDetail, ContainerResizeDetail, DragEndDetail, DragMoveDetail, DragStartDetail, InitDetail, PosterClickDetail, SlideItemLoadDetail } from '../lg-events';
export default class Lightgallery extends Vue {
    $refs: {
        container: HTMLElement;
    };
    settings: LightGallerySettings;
    onAfterAppendSlide: (detail: AfterSlideDetail) => void;
    onInit: (detail: InitDetail) => void;
    onHasVideo: (detail: InitDetail) => void;
    onContainerResize: (detail: ContainerResizeDetail) => void;
    onAfterAppendSubHtml: (detail: AfterAppendSubHtmlDetail) => void;
    onBeforeOpen: (detail: BeforeOpenDetail) => void;
    onAfterOpen: (detail: AfterOpenDetail) => void;
    onSlideItemLoad: (detail: SlideItemLoadDetail) => void;
    onBeforeSlide: (detail: BeforeSlideDetail) => void;
    onAfterSlide: (detail: AfterSlideDetail) => void;
    onPosterClick: (detail: PosterClickDetail) => void;
    onDragStart: (detail: DragStartDetail) => void;
    onDragMove: (detail: DragMoveDetail) => void;
    onDragEnd: (detail: DragEndDetail) => void;
    onBeforeNextSlide: (detail: BeforeNextSlideDetail) => void;
    onBeforePrevSlide: (detail: BeforePrevSlideDetail) => void;
    onBeforeClose: (detail: BeforeCloseDetail) => void;
    onAfterClose: (detail: AfterCloseDetail) => void;
    onRotateLeft?: (detail: RotateLeftDetail) => void;
    onRotateRight?: (detail: RotateRightDetail) => void;
    onFlipHorizontal?: (detail: FlipHorizontalDetail) => void;
    onFlipVertical?: (detail: FlipVerticalDetail) => void;
    LG: LGPlugin;
    mounted(): void;
    unmounted(): void;
    private getMethodName;
    private registerEvents;
}

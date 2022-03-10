/**
 * lightGallery caption for placing captions relative to the image
 */
import { LightGallery } from '../../lightgallery';
import { RelativeCaptionSettings } from './lg-relative-caption-settings';
export default class RelativeCaption {
    core: LightGallery;
    settings: RelativeCaptionSettings;
    constructor(instance: LightGallery);
    init(): void;
    private setCaptionStyle;
    private setRelativeCaption;
    destroy(): void;
}

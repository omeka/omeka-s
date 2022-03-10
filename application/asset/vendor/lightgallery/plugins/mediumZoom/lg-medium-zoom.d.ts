import { LgQuery } from '../../lgQuery';
import { LightGallery } from '../../lightgallery';
import { MediumZoomSettings } from './lg-medium-zoom-settings';
export default class MediumZoom {
    core: LightGallery;
    settings: MediumZoomSettings;
    private $LG;
    constructor(instance: LightGallery, $LG: LgQuery);
    private toggleItemClass;
    init(): void;
    destroy(): void;
}

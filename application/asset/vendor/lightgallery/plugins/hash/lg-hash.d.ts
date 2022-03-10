import { LgQuery } from '../../lgQuery';
import { LightGallery } from '../../lightgallery';
import { HashSettings } from './lg-hash-settings';
export default class Hash {
    core: LightGallery;
    settings: HashSettings;
    oldHash: string;
    private $LG;
    constructor(instance: LightGallery, $LG: LgQuery);
    init(): void;
    private onAfterSlide;
    /**
     * Get index of the slide from custom slideName. Has to be a public method. Used in hash plugin
     * @param {String} hash
     * @returns {Number} Index of the slide.
     */
    getIndexFromUrl(hash?: string): number;
    buildFromHash(): boolean | undefined;
    private onCloseAfter;
    private onHashchange;
    closeGallery(): void;
    destroy(): void;
}

export interface HashSettings {
    /**
     * Enable/Disable hash option
     */
    hash: boolean;
    /**
     * Unique id for each gallery.
     * @description It is mandatory when you use hash plugin for multiple galleries on the same page.
     */
    galleryId: string;
    /**
     * Custom slide name to use in the url when hash plugin is enabled
     */
    customSlideName: boolean;
}
export declare const hashSettings: HashSettings;

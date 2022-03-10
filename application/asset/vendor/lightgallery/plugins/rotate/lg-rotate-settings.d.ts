export interface RotateStrings {
    flipVertical: string;
    flipHorizontal: string;
    rotateLeft: string;
    rotateRight: string;
}
export interface RotateSettings {
    /**
     * Enable/Disable rotate option
     */
    rotate: boolean;
    /**
     * Rotate speed in milliseconds
     */
    rotateSpeed: number;
    /**
     * Enable rotate left.
     */
    rotateLeft: boolean;
    /**
     * Enable rotate right.
     */
    rotateRight: boolean;
    /**
     * Enable flip horizontal.
     */
    flipHorizontal: boolean;
    /**
     * Enable flip vertical.
     */
    flipVertical: boolean;
    /**
     * Custom translation strings for aria-labels
     */
    rotatePluginStrings: RotateStrings;
}
export declare const rotateSettings: {
    rotate: boolean;
    rotateSpeed: number;
    rotateLeft: boolean;
    rotateRight: boolean;
    flipHorizontal: boolean;
    flipVertical: boolean;
    rotatePluginStrings: RotateStrings;
};

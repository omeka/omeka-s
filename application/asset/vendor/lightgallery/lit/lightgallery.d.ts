/**
 * lightGallery component for lit
 */
import { LitElement } from 'lit';
import { LightGallerySettings } from 'lightgallery/lg-settings';
import { TemplateResult } from 'lit';
export declare class LightGalleryLit extends LitElement {
    private galleryInstance?;
    private getSelector;
    firstUpdated(): void;
    settings: LightGallerySettings;
    handleSlotchange(e: any): void;
    disconnectedCallback(): void;
    render(): TemplateResult;
}
declare global {
    interface HTMLElementTagNameMap {
        'light-gallery': LightGalleryLit;
    }
}
//# sourceMappingURL=lightgallery.d.ts.map
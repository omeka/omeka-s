import * as L from 'leaflet';
import { ControlPosition, FeatureGroup, MarkerOptions, Map } from 'leaflet';
import SearchElement from './SearchElement';
import ResultList from './resultList';
import { SearchResult } from './providers/provider';
import { Provider } from './providers';
interface SearchControlProps {
    /** the provider to use for searching */
    provider: Provider;
    /** the leaflet position to render the element in */
    position: ControlPosition;
    /**
     * the stye of the search element
     * @default bar
     **/
    style: 'button' | 'bar';
    marker: MarkerOptions;
    maxMarkers: number;
    showMarker: boolean;
    showPopup: boolean;
    popupFormat<T = any>(args: {
        query: Selection;
        result: SearchResult<T>;
    }): string;
    resultFormat<T = any>(args: {
        result: SearchResult<T>;
    }): string;
    searchLabel: string;
    clearSearchLabel: string;
    notFoundMessage: string;
    messageHideDelay: number;
    animateZoom: boolean;
    zoomLevel: number;
    retainZoomLevel: boolean;
    classNames: {
        container: string;
        button: string;
        resetButton: string;
        msgbox: string;
        form: string;
        input: string;
        resultlist: string;
        item: string;
        notfound: string;
    };
    autoComplete: boolean;
    autoCompleteDelay: number;
    maxSuggestions: number;
    autoClose: boolean;
    keepResult: boolean;
    updateMap: boolean;
}
export declare type SearchControlOptions = Partial<SearchControlProps> & {
    provider: Provider;
};
interface Selection {
    query: string;
    data?: SearchResult;
}
interface SearchControl {
    options: Omit<SearchControlProps, 'provider'> & {
        provider?: SearchControlProps['provider'];
    };
    markers: FeatureGroup;
    searchElement: SearchElement;
    resultList: ResultList;
    classNames: SearchControlProps['classNames'];
    container: HTMLDivElement;
    input: HTMLInputElement;
    button: HTMLAnchorElement;
    resetButton: HTMLAnchorElement;
    map: Map;
    initialize(options: SearchControlProps): void;
    onSubmit(result: Selection): void;
    open(): void;
    close(): void;
    onClick(event: Event): void;
    clearResults(event?: KeyboardEvent | null, force?: boolean): void;
    autoSearch(event: KeyboardEvent): void;
    selectResult(event: KeyboardEvent): void;
    showResult(result: SearchResult, query: Selection): void;
    addMarker(result: SearchResult, selection: Selection): void;
    centerMap(result: SearchResult): void;
    closeResults(): void;
    getZoom(): number;
    onAdd(map: Map): HTMLDivElement;
    onRemove(): SearchControl;
}
export default function SearchControl(...options: any[]): SearchControl & L.Control;
export {};

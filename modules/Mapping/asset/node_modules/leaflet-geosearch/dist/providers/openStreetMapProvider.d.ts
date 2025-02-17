import AbstractProvider, { EndpointArgument, ParseArgument, ProviderOptions, SearchResult } from './provider';
export declare type RequestResult = RawResult[];
export interface RawResult {
    place_id: string;
    license: string;
    osm_type: string;
    osm_id: number;
    boundingbox: [string, string, string, string];
    lat: string;
    lon: string;
    display_name: string;
    class: string;
    type: string;
    importance: number;
    icon?: string;
}
export declare type OpenStreetMapProviderOptions = {
    searchUrl?: string;
    reverseUrl?: string;
} & ProviderOptions;
export default class OpenStreetMapProvider extends AbstractProvider<RawResult[], RawResult> {
    searchUrl: string;
    reverseUrl: string;
    constructor(options?: OpenStreetMapProviderOptions);
    endpoint({ query, type }: EndpointArgument): string;
    parse(response: ParseArgument<RequestResult>): SearchResult<RawResult>[];
}

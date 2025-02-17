/// <reference types="google.maps" />
import AbstractProvider, { EndpointArgument, ParseArgument, ProviderOptions, SearchArgument, SearchResult } from './provider';
import { LoaderOptions } from '@googlemaps/js-api-loader';
interface RequestResult {
    results: google.maps.GeocoderResult[];
    status?: google.maps.GeocoderStatus;
}
export declare type GoogleProviderOptions = LoaderOptions & ProviderOptions;
export default class GoogleProvider extends AbstractProvider<RequestResult, google.maps.GeocoderResult> {
    loader: Promise<google.maps.Geocoder> | null;
    geocoder: google.maps.Geocoder | null;
    constructor(options: GoogleProviderOptions);
    endpoint({ query }: EndpointArgument): never;
    parse(response: ParseArgument<RequestResult>): SearchResult<google.maps.GeocoderResult>[];
    search(options: SearchArgument): Promise<SearchResult<google.maps.GeocoderResult>[]>;
}
export {};

import AbstractProvider, { EndpointArgument, ParseArgument, ProviderOptions, SearchResult } from './provider';
export interface RequestResult {
    geocoding: object;
    features: RawResult[];
}
export declare type PeliasProviderOptions = {
    host?: string;
} & ProviderOptions;
export interface RawResult {
    type: 'Feature';
    geometry: {
        type: 'Point';
        coordinates: [number, number];
    };
    bbox?: [number, number, number, number];
    properties: {
        id: string;
        source_id: string;
        gid: string;
        layer: string;
        source: string;
        label: string;
        name: string;
        accuracy: 'centroid' | 'point';
        confidence?: number;
        match_type?: 'exact' | 'interpolated' | 'fallback';
        borough?: string;
        borough_a?: string;
        borough_gid?: string;
        continent?: string;
        continent_a?: string;
        continent_gid?: string;
        country?: string;
        country_a?: string;
        country_gid?: string;
        county?: string;
        county_a?: string;
        county_gid?: string;
        dependency?: string;
        dependency_a?: string;
        dependency_gid?: string;
        empire?: string;
        empire_a?: string;
        empire_gid?: string;
        localadmin?: string;
        localadmin_a?: string;
        localadmin_gid?: string;
        locality?: string;
        locality_a?: string;
        locality_gid?: string;
        macrocounty?: string;
        macrocounty_a?: string;
        macrocounty_gid?: string;
        macroregion?: string;
        macroregion_a?: string;
        macroregion_gid?: string;
        marinearea?: string;
        marinearea_a?: string;
        marinearea_gid?: string;
        neighbourhood?: string;
        neighbourhood_a?: string;
        neighbourhood_gid?: string;
        ocean?: string;
        ocean_a?: string;
        ocean_gid?: string;
        postalcode?: string;
        postalcode_a?: string;
        postalcode_gid?: string;
        region?: string;
        region_a?: string;
        region_gid?: string;
        street?: string;
        housenumber?: string;
        addendum?: any;
    };
}
export default class PeliasProvider extends AbstractProvider<RequestResult, RawResult> {
    host: string;
    constructor(options?: PeliasProviderOptions);
    /**
     * note: Pelias has four different query modes:
     * /v1/autocomplete: for partially completed inputs (such as when a user types)
     * /v1/search: for completed inputs (such as when geocoding a CSV file)
     * /v1/search/structured: for completed inputs with fields already separated
     * /v1/reverse: for finding places nearby/enveloping a point
     */
    endpoint({ query, type }: EndpointArgument): string;
    parse(response: ParseArgument<RequestResult>): SearchResult<RawResult>[];
}

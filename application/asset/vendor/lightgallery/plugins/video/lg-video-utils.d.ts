import { VideoInfo } from '../../types';
export declare type PlayerParams = Record<string, string | number | boolean> | boolean;
export declare const param: (obj: {
    [x: string]: string | number | boolean;
}) => string;
export declare const getVimeoURLParams: (defaultParams: PlayerParams, videoInfo?: VideoInfo | undefined) => string;

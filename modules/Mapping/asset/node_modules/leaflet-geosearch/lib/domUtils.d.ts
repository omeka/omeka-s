export declare function createElement<T extends HTMLElement = HTMLElement>(element: string, className?: string | null, parent?: Element | null, attributes?: {
    [key: string]: string | ((event: any) => void);
}): T;
export declare function stopPropagation(event: Event): void;
export declare function createScriptElement<T = object>(url: string, cb: string): Promise<T>;
export declare const cx: (...classNames: (string | undefined)[]) => string;
export declare function addClassName(element: Element | null, className: string | string[]): void;
export declare function removeClassName(element: Element | null, className: string | string[]): void;
export declare function replaceClassName(element: Element, find: string, replace: string): void;

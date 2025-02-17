/* eslint-disable @typescript-eslint/no-explicit-any */
export default function debounce(cb, wait = 250, immediate = false) {
    let timeout;
    return (...args) => {
        if (timeout) {
            clearTimeout(timeout);
        }
        timeout = setTimeout(() => {
            timeout = null;
            if (!immediate) {
                cb(...args);
            }
        }, wait);
        if (immediate && !timeout) {
            cb(...args);
        }
    };
}
//# sourceMappingURL=debounce.js.map
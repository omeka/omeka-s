export function createElement(element, className = '', parent, attributes = {}) {
    const el = document.createElement(element);
    if (className) {
        el.className = className;
    }
    Object.keys(attributes).forEach((key) => {
        if (typeof attributes[key] === 'function') {
            // IE doesn't support startsWith
            const type = (key.indexOf('on') === 0 ? key.substr(2).toLowerCase() : key);
            el.addEventListener(type, attributes[key]);
        }
        else if (key === 'html') {
            el.innerHTML = attributes[key];
        }
        else if (key === 'text') {
            el.innerText = attributes[key];
        }
        else {
            el.setAttribute(key, attributes[key]);
        }
    });
    if (parent) {
        parent.appendChild(el);
    }
    return el;
}
export function stopPropagation(event) {
    event.preventDefault();
    event.stopPropagation();
}
export function createScriptElement(url, cb) {
    const script = createElement('script', null, document.body);
    script.setAttribute('type', 'text/javascript');
    return new Promise((resolve) => {
        window[cb] = (json) => {
            script.remove();
            delete window[cb];
            resolve(json);
        };
        script.setAttribute('src', url);
    });
}
export const cx = (...classNames) => classNames.filter(Boolean).join(' ').trim();
export function addClassName(element, className) {
    if (!element || !element.classList) {
        return;
    }
    // IE doesn't support adding multiple classes at once :(
    const classNames = Array.isArray(className) ? className : [className];
    classNames.forEach((name) => {
        if (!element.classList.contains(name)) {
            element.classList.add(name);
        }
    });
}
export function removeClassName(element, className) {
    if (!element || !element.classList) {
        return;
    }
    // IE doesn't support removing multiple classes at once :(
    const classNames = Array.isArray(className) ? className : [className];
    classNames.forEach((name) => {
        if (element.classList.contains(name)) {
            element.classList.remove(name);
        }
    });
}
export function replaceClassName(element, find, replace) {
    removeClassName(element, find);
    addClassName(element, replace);
}
//# sourceMappingURL=domUtils.js.map
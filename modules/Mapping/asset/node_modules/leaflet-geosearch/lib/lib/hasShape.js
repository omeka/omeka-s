export default function hasShape(keys, exact, object) {
    if (exact && keys.length !== Object.keys(object).length) {
        return false;
    }
    if (exact && keys.some((x) => !object.hasOwnProperty(x))) {
        return false;
    }
    if (!keys.some((x) => object.hasOwnProperty(x))) {
        return false;
    }
    return true;
}
//# sourceMappingURL=hasShape.js.map
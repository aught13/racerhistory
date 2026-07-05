const MOBILE_VIEWPORT_MAX = 991;
const LOW_BANDWIDTH_TYPES = new Set(["slow-2g", "2g", "3g"]);

export function getNetworkProfile(connection) {
    const effectiveType = connection?.effectiveType || "";
    const saveData = connection?.saveData === true;
    const isLowBandwidth =
        saveData ||
        (effectiveType !== "" && LOW_BANDWIDTH_TYPES.has(effectiveType));

    return {
        effectiveType,
        saveData,
        isLowBandwidth,
    };
}

export function getRuntimeProfile(options = {}) {
    const hasWindow = typeof window !== "undefined";
    const hasNavigator = typeof navigator !== "undefined";

    const pathname =
        options.pathname ?? (hasWindow ? window.location.pathname : "/");
    const viewportWidth =
        options.viewportWidth ??
        (hasWindow ? window.innerWidth : MOBILE_VIEWPORT_MAX + 1);
    const connection =
        options.connection ?? (hasNavigator ? navigator.connection : undefined);

    return {
        pathname,
        isAdminPath: pathname.startsWith("/admin"),
        isMobileViewport: viewportWidth <= MOBILE_VIEWPORT_MAX,
        ...getNetworkProfile(connection),
    };
}

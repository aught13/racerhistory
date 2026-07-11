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

    // Determine viewport width with multiple fallbacks. In some test
    // environments `window.innerWidth` may not yet reflect the device
    // metrics when runtime code runs very early, so also check
    // `visualViewport` and `matchMedia` where available.
    let viewportWidth = options.viewportWidth;
    if (typeof viewportWidth === "undefined") {
        if (hasWindow) {
            viewportWidth =
                window.innerWidth ||
                (window.visualViewport && window.visualViewport.width) ||
                null;
            if (
                viewportWidth === null &&
                typeof window.matchMedia === "function"
            ) {
                // matchMedia gives a reliable CSS-media result even early.
                try {
                    const mq = window.matchMedia(
                        `(max-width: ${MOBILE_VIEWPORT_MAX}px)`,
                    );
                    viewportWidth = mq.matches
                        ? MOBILE_VIEWPORT_MAX
                        : MOBILE_VIEWPORT_MAX + 1;
                } catch {
                    viewportWidth = MOBILE_VIEWPORT_MAX + 1;
                }
            }
        } else {
            viewportWidth = MOBILE_VIEWPORT_MAX + 1;
        }
    }
    const connection =
        options.connection ?? (hasNavigator ? navigator.connection : undefined);

    // Heuristic mobile detection: viewport width OR common mobile UA tokens.
    const uaIsMobile = hasNavigator
        ? /Mobi|Android|iPhone|iPad|iPod/i.test(navigator.userAgent)
        : false;

    return {
        pathname,
        isAdminPath: pathname.startsWith("/admin"),
        isMobileViewport: viewportWidth <= MOBILE_VIEWPORT_MAX || uaIsMobile,
        ...getNetworkProfile(connection),
    };
}

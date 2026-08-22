import * as Turbo from "@hotwired/turbo";
import { Application } from "@hotwired/stimulus";

import { initThemeFromCookie } from "./lib/theme.js";
import {
    initAdminRuntimeLifecycle,
    enforceAdminLightTheme,
} from "./lib/admin_runtime.js";
import { startNativeBridge } from "./lib/native_bridge.js";
import { registerServiceWorker } from "./lib/pwa.js";
import { initGoogleAdSlots } from "./lib/google_ads.js";
import { initTurboScrollBehavior } from "./lib/turbo_scroll.js";
import { initTinyMceLoader } from "./lib/tinymce_loader.js";
import { initializeLegacyModules } from "./lib/legacy_loader_registry.js";
import { getRuntimeProfile } from "./lib/runtime_profile.js";

const isAdminPath =
    typeof window !== "undefined" &&
    window.location.pathname.startsWith("/admin");

const hasWindow = typeof window !== "undefined";
const runtimeAlreadyBooted = hasWindow && window.__RH_RUNTIME_BOOTED__ === true;

function isElementNode(value) {
    return (
        typeof globalThis !== "undefined" &&
        typeof globalThis.Element === "function" &&
        value instanceof globalThis.Element
    );
}

function isAdminUrl(urlLike) {
    if (typeof window === "undefined" || !urlLike) {
        return false;
    }

    try {
        const parsed = new URL(String(urlLike), window.location.origin);

        return parsed.pathname.startsWith("/admin");
    } catch {
        return false;
    }
}

function ensureAdminThemeLifecycleForCurrentPath() {
    if (typeof window === "undefined") {
        return;
    }

    if (!window.location.pathname.startsWith("/admin")) {
        return;
    }

    // Ensure admin lifecycle listeners are installed even when the app first
    // booted on a public (possibly dark-mode) route and later Turbo-navigated
    // into /admin.
    initAdminRuntimeLifecycle();
    enforceAdminLightTheme();
}

if (!runtimeAlreadyBooted) {
    if (hasWindow) {
        window.__RH_RUNTIME_BOOTED__ = true;
        window.Turbo = Turbo;
    }

    // On admin paths, enforce light theme FIRST before any cookie-based theme
    // is applied. This prevents dark mode from bleeding through during page
    // transitions from public routes to admin.
    if (isAdminPath) {
        enforceAdminLightTheme();
    }

    // Initialize theme system on public paths (respects user preference).
    // On admin paths, this just sets up the media query listener without
    // overriding the light theme already enforced above.
    if (!isAdminPath) {
        initThemeFromCookie();
        void import("./legacy/image-retry.mjs");
    } else {
        // Initialize admin runtime which sets up Turbo event listeners
        // to maintain light theme throughout admin session
        initAdminRuntimeLifecycle();
    }

    startNativeBridge();
    // Service worker registration is async but non-blocking for the app
    void registerServiceWorker();
    initGoogleAdSlots(document);
    initTurboScrollBehavior();
    initTinyMceLoader();
    const stimulus = Application.start();
    // Expose Stimulus application globally so eager module imports can
    // register controllers directly instead of relying on fallbacks.
    if (hasWindow) {
        window.StimulusApplication = stimulus;
    }
    // Eagerly load the critical admin core on admin pages to reduce
    // test-suite and runtime flakiness where dynamic imports may be
    // delayed under heavy concurrency. Register controllers directly
    // with the Stimulus application when available.
    try {
        if (isAdminPath) {
            void import("./route_modules/admin_core.js").then((mod) => {
                try {
                    mod.registerAdminCoreControllers(stimulus);
                } catch (e) {
                    console.debug(
                        "main: failed to eagerly register admin_core",
                        e,
                    );
                }
            });
        }
    } catch {
        void 0;
    }

    initializeLegacyModules(stimulus);
}

if (hasWindow && !window.__RH_ADMIN_PATH_THEME_WATCHER_INIT__) {
    window.__RH_ADMIN_PATH_THEME_WATCHER_INIT__ = true;

    document.addEventListener("turbo:before-visit", (event) => {
        if (isAdminUrl(event?.detail?.url)) {
            enforceAdminLightTheme();
        }
    });

    document.addEventListener("turbo:before-render", (event) => {
        const nextBody = event?.detail?.newBody;
        const looksLikeAdminBody =
            !!nextBody &&
            typeof nextBody.classList?.contains === "function" &&
            nextBody.classList.contains("sidebar-mini");

        if (looksLikeAdminBody || isAdminUrl(event?.detail?.newFrame?.src)) {
            ensureAdminThemeLifecycleForCurrentPath();
            // Repeat once on the next turn to guard against late-applied
            // dark-mode attrs while Turbo is finalizing the render.
            window.setTimeout(ensureAdminThemeLifecycleForCurrentPath, 0);
        }
    });

    document.addEventListener("turbo:load", () => {
        ensureAdminThemeLifecycleForCurrentPath();
    });

    document.addEventListener("turbo:render", () => {
        ensureAdminThemeLifecycleForCurrentPath();
    });

    window.addEventListener("pageshow", () => {
        ensureAdminThemeLifecycleForCurrentPath();
    });

    if (document.readyState === "loading") {
        document.addEventListener(
            "DOMContentLoaded",
            () => {
                ensureAdminThemeLifecycleForCurrentPath();
            },
            { once: true },
        );
    } else {
        ensureAdminThemeLifecycleForCurrentPath();
    }
}

document.addEventListener("turbo:load", () => {
    initGoogleAdSlots(document);
});

document.addEventListener("turbo:frame-load", (event) => {
    const frame = event?.target;
    initGoogleAdSlots(isElementNode(frame) ? frame : document);
});

// Eagerly load the admin stats entry module when the multi-add markup is
// present so we avoid a race where deferred loading leaves no handler
// attached at the moment of a user click (causes E2E flakes). Run this
// regardless of the runtime boot flag so hot reloads / Turbo navigations
// still ensure the handler is present.
try {
    if (typeof document !== "undefined") {
        const shouldImportStatsModule =
            !!document.getElementById("add-row-btn") ||
            (typeof window !== "undefined" &&
                (window.location.pathname.startsWith(
                    "/admin/stat-basket-game-person",
                ) ||
                    window.location.pathname.startsWith(
                        "/admin/stat-basket-game-opponent",
                    )));

        if (shouldImportStatsModule) {
            // Only eagerly import the admin stats entry module on non-mobile
            // and non-low-bandwidth clients. On constrained clients we prefer
            // the deferred/interaction strategy to avoid loading heavy admin
            // logic until the user interacts (matches the loader strategy
            // heuristics used elsewhere).
            const profile = getRuntimeProfile();
            const constrained =
                profile.isMobileViewport || profile.isLowBandwidth;

            const doImport = () => {
                void import("./route_modules/admin_stats_entry.js").then(
                    (mod) => {
                        try {
                            mod.registerAdminStatsEntryControllers(
                                window.StimulusApplication,
                            );
                        } catch (e) {
                            console.debug(
                                "main: failed to register admin_stats_entry controllers",
                                e,
                            );
                        }
                    },
                );
            };

            if (!constrained) {
                if (document.readyState === "loading") {
                    document.addEventListener("DOMContentLoaded", doImport, {
                        once: true,
                    });
                } else {
                    doImport();
                }
            }
        }
    }
} catch {
    void 0;
}

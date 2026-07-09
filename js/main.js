import * as Turbo from "@hotwired/turbo";
import { Application } from "@hotwired/stimulus";

import { initThemeFromCookie } from "./lib/theme.js";
import { initAdminRuntimeLifecycle } from "./lib/admin_runtime.js";
import { startNativeBridge } from "./lib/native_bridge.js";
import { registerServiceWorker } from "./lib/pwa.js";
import { initTurboScrollBehavior } from "./lib/turbo_scroll.js";
import { initTinyMceLoader } from "./lib/tinymce_loader.js";
import { initializeLegacyModules } from "./lib/legacy_loader_registry.js";

const isAdminPath =
    typeof window !== "undefined" &&
    window.location.pathname.startsWith("/admin");

const hasWindow = typeof window !== "undefined";
const runtimeAlreadyBooted = hasWindow && window.__RH_RUNTIME_BOOTED__ === true;

if (!runtimeAlreadyBooted) {
    if (hasWindow) {
        window.__RH_RUNTIME_BOOTED__ = true;
        window.Turbo = Turbo;
    }

    if (!isAdminPath) {
        initThemeFromCookie();
        void import("./legacy/image-retry.mjs");
    } else {
        initAdminRuntimeLifecycle();
    }

    startNativeBridge();
    registerServiceWorker();
    initTurboScrollBehavior();
    initTinyMceLoader();
    const stimulus = Application.start();
    // Expose Stimulus application globally so eager module imports can
    // register controllers directly instead of relying on fallbacks.
    if (hasWindow) {
        window.StimulusApplication = stimulus;
    }
    initializeLegacyModules(stimulus);
}

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
            const doImport = () => {
                void import("./route_modules/admin_stats_entry.js").then(
                    (mod) => {
                        try {
                            // Call the register function even if we don't have a Stimulus
                            // instance available; the function contains a try/catch and
                            // will run the legacy initializer as a fallback.
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

            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", doImport, {
                    once: true,
                });
            } else {
                doImport();
            }
        }
    }
} catch {
    void 0;
}

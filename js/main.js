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
    initializeLegacyModules(stimulus);
}

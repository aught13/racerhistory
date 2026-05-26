import * as Turbo from "@hotwired/turbo";
import { Application } from "@hotwired/stimulus";

import ThemeToggleController from "./controllers/theme_toggle_controller.js";

import { initThemeFromCookie } from "./theme.js";
import { startNativeBridge } from "./native_bridge.js";
import { registerServiceWorker } from "./pwa.js";
import { initTurboScrollBehavior } from "./turbo_scroll.js";

const runtimeAlreadyBooted =
    typeof window !== "undefined" && window.__RH_RUNTIME_BOOTED__ === true;

if (!runtimeAlreadyBooted) {
    window.__RH_RUNTIME_BOOTED__ = true;
    // Expose for debugging in development.
    window.Turbo = Turbo;

    initThemeFromCookie();
    startNativeBridge();
    registerServiceWorker();
    initTurboScrollBehavior();

    const stimulus = Application.start();
    stimulus.register("theme-toggle", ThemeToggleController);
}

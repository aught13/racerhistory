import * as Turbo from "@hotwired/turbo";
import { Application } from "@hotwired/stimulus";

import ThemeToggleController from "./controllers/theme_toggle_controller.js";

import { initThemeFromCookie } from "./theme.js";
import { startNativeBridge } from "./native_bridge.js";
import { registerServiceWorker } from "./pwa.js";
import { initTurboScrollBehavior } from "./turbo_scroll.js";

// Expose for debugging in development.
window.Turbo = Turbo;

initThemeFromCookie();
startNativeBridge();
registerServiceWorker();
initTurboScrollBehavior();

const stimulus = Application.start();
stimulus.register("theme-toggle", ThemeToggleController);

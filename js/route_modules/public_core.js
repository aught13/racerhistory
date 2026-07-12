// Ensure Bootstrap's JS bundle is available for public pages (collapse, dropdowns, etc.)
import "bootstrap/dist/js/bootstrap.bundle.min.js";
import PublicShellController from "../controllers/public_shell_controller.js";
import NavAccordionController from "../controllers/nav_accordion_controller.js";
import ThemeToggleController from "../controllers/theme_toggle_controller.js";

export function registerPublicCoreControllers(stimulus) {
    stimulus.register("public-shell", PublicShellController);
    stimulus.register("nav-accordion", NavAccordionController);
    stimulus.register("theme-toggle", ThemeToggleController);

    // Prefetch heavier page modules on nav hover/interact to reduce
    // race conditions during Turbo navigation (e.g. clicking "People").
    try {
        if (
            typeof window !== "undefined" &&
            !window.__RH_PREFETCH_INSTALLED__
        ) {
            window.__RH_PREFETCH_INSTALLED__ = true;
            document.addEventListener(
                "pointerenter",
                (e) => {
                    const a =
                        e.target &&
                        e.target.closest &&
                        e.target.closest("a[href]");
                    if (!a) {
                        return;
                    }
                    let url;
                    try {
                        url = new URL(a.href, window.location.origin);
                    } catch (err) {
                        console.debug(err);
                        return;
                    }

                    if (url.pathname === "/people") {
                        void import("../route_modules/public_people.js").catch(
                            () => null,
                        );
                    }
                },
                { capture: true, passive: true },
            );
        }
    } catch (err) {
        console.debug(err);
    }
}

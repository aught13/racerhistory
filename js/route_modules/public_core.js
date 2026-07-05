import PublicShellController from "../controllers/public_shell_controller.js";
import NavAccordionController from "../controllers/nav_accordion_controller.js";
import ThemeToggleController from "../controllers/theme_toggle_controller.js";

export function registerPublicCoreControllers(stimulus) {
    stimulus.register("public-shell", PublicShellController);
    stimulus.register("nav-accordion", NavAccordionController);
    stimulus.register("theme-toggle", ThemeToggleController);
}

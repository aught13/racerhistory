import "admin-lte/dist/css/adminlte.min.css";

import AdminLayoutController from "../controllers/admin_layout_controller.js";
import AdminDashboardController from "../controllers/admin_dashboard_controller.js";
import NavAccordionController from "../controllers/nav_accordion_controller.js";

export function registerAdminCoreControllers(stimulus) {
    stimulus.register("admin-layout", AdminLayoutController);
    stimulus.register("admin-dashboard", AdminDashboardController);
    stimulus.register("nav-accordion", NavAccordionController);
}
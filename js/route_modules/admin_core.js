import "admin-lte/dist/css/adminlte.min.css";

import AdminLayoutController from "../controllers/admin_layout_controller.js";
import AdminDashboardController from "../controllers/admin_dashboard_controller.js";
import NavAccordionController from "../controllers/nav_accordion_controller.js";
import TagSelectionController from "../controllers/tag_selection_controller.js";
import TagModalController from "../controllers/tag_modal_controller.js";
import PlaceLocationController from "../controllers/place_location_controller.js";

export function registerAdminCoreControllers(stimulus) {
    stimulus.register("admin-layout", AdminLayoutController);
    stimulus.register("admin-dashboard", AdminDashboardController);
    stimulus.register("nav-accordion", NavAccordionController);
    stimulus.register("tag-selection", TagSelectionController);
    stimulus.register("tag-modal", TagModalController);
    stimulus.register("place-location", PlaceLocationController);
}

import PlaceSearchController from "../controllers/place_search_controller.js";
import RosterEditPersonController from "../controllers/roster_edit_person_controller.js";
import RosterMultiAddController from "../controllers/roster_multi_add_controller.js";

export function registerAdminRostersControllers(stimulus) {
    stimulus.register("place-search", PlaceSearchController);
    stimulus.register("roster-edit-person", RosterEditPersonController);
    stimulus.register("roster-multi-add", RosterMultiAddController);
}

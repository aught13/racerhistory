import BackNavigationController from "../controllers/back_navigation_controller.js";
import ImageSelectorController from "../controllers/image_selector_controller.js";
import PersonFormController from "../controllers/person_form_controller.js";
import PersonsIndexController from "../controllers/persons_index_controller.js";
import PlaceSearchController from "../controllers/place_search_controller.js";

export function registerAdminPeopleControllers(stimulus) {
    stimulus.register("back-navigation", BackNavigationController);
    stimulus.register("image-selector", ImageSelectorController);
    stimulus.register("person-form", PersonFormController);
    stimulus.register("persons-index", PersonsIndexController);
    stimulus.register("place-search", PlaceSearchController);
}

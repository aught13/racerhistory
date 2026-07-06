import AdminGamesIndexController from "../controllers/admin_games_index_controller.js";
import AdminGameFormController from "../controllers/admin_game_form_controller.js";
import GameViewController from "../controllers/game_view_controller.js";
import GameBoxTotalsToggleController from "../controllers/game_box_totals_toggle_controller.js";
import PlaceLocationController from "../controllers/place_location_controller.js";

export function registerAdminGamesControllers(stimulus) {
    stimulus.register("admin-games-index", AdminGamesIndexController);
    stimulus.register("admin-game-form", AdminGameFormController);
    stimulus.register("game-view", GameViewController);
    stimulus.register("game-box-totals-toggle", GameBoxTotalsToggleController);
    stimulus.register("place-location", PlaceLocationController);
}
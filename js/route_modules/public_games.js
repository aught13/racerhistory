import "../lib/public_vite_datatables.mjs";

import GameViewController from "../controllers/game_view_controller.js";
import GamesSearchController from "../controllers/games_search_controller.js";
import GameBoxTotalsToggleController from "../controllers/game_box_totals_toggle_controller.js";
import SeriesOpponentsController from "../controllers/series_opponents_controller.js";

export function registerPublicGamesControllers(stimulus) {
    stimulus.register("game-view", GameViewController);
    stimulus.register("games-search", GamesSearchController);
    stimulus.register("game-box-totals-toggle", GameBoxTotalsToggleController);
    stimulus.register("series-opponents", SeriesOpponentsController);
}
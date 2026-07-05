import "../lib/public_vite_datatables.mjs";

import SeasonsPageController from "../controllers/seasons_page_controller.js";
import SeasonViewController from "../controllers/season_view_controller.js";

export function registerPublicSeasonsControllers(stimulus) {
    stimulus.register("seasons-page", SeasonsPageController);
    stimulus.register("season-view", SeasonViewController);
}
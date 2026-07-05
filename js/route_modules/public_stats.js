import "../lib/public_vite_datatables.mjs";

import StatsPageController from "../controllers/stats_page_controller.js";

export function registerPublicStatsControllers(stimulus) {
    stimulus.register("stats-page", StatsPageController);
}

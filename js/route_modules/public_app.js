import "../lib/public_vite_datatables.mjs";

import PublicShellController from "../controllers/public_shell_controller.js";
import NavAccordionController from "../controllers/nav_accordion_controller.js";
import ThemeToggleController from "../controllers/theme_toggle_controller.js";
import BlogInteractionsController from "../controllers/blog_interactions_controller.js";
import GameViewController from "../controllers/game_view_controller.js";
import GamesSearchController from "../controllers/games_search_controller.js";
import GameBoxTotalsToggleController from "../controllers/game_box_totals_toggle_controller.js";
import PeopleIndexController from "../controllers/people_index_controller.js";
import PersonGameLogTabsController from "../controllers/person_game_log_tabs_controller.js";
import PersonBlogPopoversController from "../controllers/person_blog_popovers_controller.js";
import SeasonsPageController from "../controllers/seasons_page_controller.js";
import SeasonViewController from "../controllers/season_view_controller.js";
import SeriesOpponentsController from "../controllers/series_opponents_controller.js";
import StatsPageController from "../controllers/stats_page_controller.js";

export function registerPublicAppControllers(stimulus) {
    stimulus.register("public-shell", PublicShellController);
    stimulus.register("nav-accordion", NavAccordionController);
    stimulus.register("theme-toggle", ThemeToggleController);
    stimulus.register("blog-interactions", BlogInteractionsController);
    stimulus.register("game-view", GameViewController);
    stimulus.register("games-search", GamesSearchController);
    stimulus.register("game-box-totals-toggle", GameBoxTotalsToggleController);
    stimulus.register("people-index", PeopleIndexController);
    stimulus.register("person-game-log-tabs", PersonGameLogTabsController);
    stimulus.register("person-blog-popovers", PersonBlogPopoversController);
    stimulus.register("seasons-page", SeasonsPageController);
    stimulus.register("season-view", SeasonViewController);
    stimulus.register("series-opponents", SeriesOpponentsController);
    stimulus.register("stats-page", StatsPageController);
}

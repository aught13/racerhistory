import "../lib/public_vite_datatables.mjs";

import PeopleIndexController from "../controllers/people_index_controller.js";
import PersonGameLogTabsController from "../controllers/person_game_log_tabs_controller.js";
import PersonBlogPopoversController from "../controllers/person_blog_popovers_controller.js";

export function registerPublicPeopleControllers(stimulus) {
    stimulus.register("people-index", PeopleIndexController);
    stimulus.register("person-game-log-tabs", PersonGameLogTabsController);
    stimulus.register("person-blog-popovers", PersonBlogPopoversController);
}
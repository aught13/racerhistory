import AdminConfirmDeleteController from "../controllers/admin_confirm_delete_controller.js";
import AdminBulkTableController from "../controllers/admin_bulk_table_controller.js";
import AdminGamesIndexController from "../controllers/admin_games_index_controller.js";
import AdminGameFormController from "../controllers/admin_game_form_controller.js";
import AdminImageBulkUploadController from "../controllers/admin_image_bulk_upload_controller.js";
import AdminImageCropThumbController from "../controllers/admin_image_crop_thumb_controller.js";
import AdminImageManipulateController from "../controllers/admin_image_manipulate_controller.js";
import AdminUsersIndexController from "../controllers/admin_users_index_controller.js";
import BackNavigationController from "../controllers/back_navigation_controller.js";
import BlogPostFormController from "../controllers/blog_post_form_controller.js";
import FieldMappingController from "../controllers/field_mapping_controller.js";
import GameViewController from "../controllers/game_view_controller.js";
import GameBoxTotalsToggleController from "../controllers/game_box_totals_toggle_controller.js";
import HeroCropController from "../controllers/hero_crop_controller.js";
import ImageSelectorController from "../controllers/image_selector_controller.js";
import ImageUploadController from "../controllers/image_upload_controller.js";
import PersonFormController from "../controllers/person_form_controller.js";
import PersonsIndexController from "../controllers/persons_index_controller.js";
import PlaceLocationController from "../controllers/place_location_controller.js";
import PlaceSearchController from "../controllers/place_search_controller.js";
import PasswordToggleController from "../controllers/password_toggle_controller.js";
import RosterEditPersonController from "../controllers/roster_edit_person_controller.js";
import RosterMultiAddController from "../controllers/roster_multi_add_controller.js";
import SeasonFormController from "../controllers/season_form_controller.js";
import SportsConfigsFormController from "../controllers/sports_configs_form_controller.js";
import SportsFormController from "../controllers/sports_form_controller.js";
import StatMultiAddController from "../controllers/stat_multi_add_controller.js";
import TeamSeasonFormController from "../controllers/team_season_form_controller.js";
import TeamSeasonImageController from "../controllers/team_season_image_controller.js";

export function registerAdminAppControllers(stimulus) {
    stimulus.register("admin-confirm-delete", AdminConfirmDeleteController);
    stimulus.register("admin-bulk-table", AdminBulkTableController);
    stimulus.register("admin-games-index", AdminGamesIndexController);
    stimulus.register("admin-game-form", AdminGameFormController);
    stimulus.register(
        "admin-image-bulk-upload",
        AdminImageBulkUploadController,
    );
    stimulus.register("admin-image-crop-thumb", AdminImageCropThumbController);
    stimulus.register("admin-image-manipulate", AdminImageManipulateController);
    stimulus.register("admin-users-index", AdminUsersIndexController);
    stimulus.register("back-navigation", BackNavigationController);
    stimulus.register("blog-post-form", BlogPostFormController);
    stimulus.register("field-mapping", FieldMappingController);
    stimulus.register("game-view", GameViewController);
    stimulus.register("game-box-totals-toggle", GameBoxTotalsToggleController);
    stimulus.register("hero-crop", HeroCropController);
    stimulus.register("image-selector", ImageSelectorController);
    stimulus.register("image-upload", ImageUploadController);
    stimulus.register("person-form", PersonFormController);
    stimulus.register("persons-index", PersonsIndexController);
    stimulus.register("place-location", PlaceLocationController);
    stimulus.register("place-search", PlaceSearchController);
    stimulus.register("password-toggle", PasswordToggleController);
    stimulus.register("roster-edit-person", RosterEditPersonController);
    stimulus.register("roster-multi-add", RosterMultiAddController);
    stimulus.register("season-form", SeasonFormController);
    stimulus.register("sports-configs-form", SportsConfigsFormController);
    stimulus.register("sports-form", SportsFormController);
    stimulus.register("stat-multi-add", StatMultiAddController);
    stimulus.register("team-season-form", TeamSeasonFormController);
    stimulus.register("team-season-image", TeamSeasonImageController);
}

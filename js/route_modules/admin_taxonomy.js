import AdminBulkTableController from "../controllers/admin_bulk_table_controller.js";
import AdminIndexTableController from "../controllers/admin_index_table_controller.js";
import FieldMappingController from "../controllers/field_mapping_controller.js";
import ImageSelectorController from "../controllers/image_selector_controller.js";
import SeasonFormController from "../controllers/season_form_controller.js";
import SportsConfigsFormController from "../controllers/sports_configs_form_controller.js";
import SportsFormController from "../controllers/sports_form_controller.js";
import TeamSeasonFormController from "../controllers/team_season_form_controller.js";
import TeamSeasonImageController from "../controllers/team_season_image_controller.js";

export function registerAdminTaxonomyControllers(stimulus) {
    stimulus.register("admin-bulk-table", AdminBulkTableController);
    stimulus.register("admin-index-table", AdminIndexTableController);
    stimulus.register("field-mapping", FieldMappingController);
    stimulus.register("image-selector", ImageSelectorController);
    stimulus.register("season-form", SeasonFormController);
    stimulus.register("sports-configs-form", SportsConfigsFormController);
    stimulus.register("sports-form", SportsFormController);
    stimulus.register("team-season-form", TeamSeasonFormController);
    stimulus.register("team-season-image", TeamSeasonImageController);
}

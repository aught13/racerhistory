import StatMultiAddController from "../controllers/stat_multi_add_controller.js";

export function registerAdminStatsEntryControllers(stimulus) {
    stimulus.register("stat-multi-add", StatMultiAddController);
}
import StatMultiAddController from "../controllers/stat_multi_add_controller.js";
import { initStatMultiAdd } from "../legacy/modules/stat-multi-add.mjs";

// Diagnostic: mark when this module is evaluated
try {
    if (typeof window !== "undefined") {
        window.__RH_ADMIN_STATS_ENTRY_MODULE_EVALUATED = true;
        console.debug("admin_stats_entry: module evaluated");
    }
} catch {
    void 0;
}

export function registerAdminStatsEntryControllers(stimulus) {
    try {
        if (typeof window !== "undefined") {
            console.debug(
                "admin_stats_entry: registerAdminStatsEntryControllers called",
                {
                    hasStimulus: !!stimulus,
                },
            );
        }
    } catch {
        void 0;
    }

    stimulus.register("stat-multi-add", StatMultiAddController);

    // Run legacy initializer only if Stimulus has not attached a controller
    // to the stat-multi-add element. This avoids double-binding handlers
    // (Stimulus + legacy) while still providing a fallback when the
    // controller hasn't connected yet.
    try {
        const container =
            document.getElementById("stat-rows") ||
            document.querySelector("[data-controller~='stat-multi-add']");
        let shouldRunLegacy = true;

        if (
            container &&
            typeof stimulus.getControllerForElementAndIdentifier === "function"
        ) {
            const ctrl = stimulus.getControllerForElementAndIdentifier(
                container,
                "stat-multi-add",
            );
            shouldRunLegacy = !ctrl;
        }

        if (shouldRunLegacy) {
            initStatMultiAdd();
        }
    } catch (e) {
        // Non-fatal: best-effort fallback
        console.debug(
            "admin_stats_entry: legacy initStatMultiAdd check failed",
            e,
        );
        try {
            initStatMultiAdd();
        } catch (err) {
            console.debug(
                "admin_stats_entry: legacy initStatMultiAdd failed",
                err,
            );
        }
    }
}

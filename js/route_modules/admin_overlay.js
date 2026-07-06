import AdminConfirmDeleteController from "../controllers/admin_confirm_delete_controller.js";

export function registerAdminOverlayControllers(stimulus) {
    stimulus.register("admin-confirm-delete", AdminConfirmDeleteController);
}
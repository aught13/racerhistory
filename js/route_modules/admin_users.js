import AdminUsersIndexController from "../controllers/admin_users_index_controller.js";
import PasswordToggleController from "../controllers/password_toggle_controller.js";

export function registerAdminUsersControllers(stimulus) {
    stimulus.register("admin-users-index", AdminUsersIndexController);
    stimulus.register("password-toggle", PasswordToggleController);
}

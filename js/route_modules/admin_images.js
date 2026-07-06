import AdminIndexTableController from "../controllers/admin_index_table_controller.js";
import AdminImageBulkUploadController from "../controllers/admin_image_bulk_upload_controller.js";
import AdminImageCropThumbController from "../controllers/admin_image_crop_thumb_controller.js";
import AdminImageManipulateController from "../controllers/admin_image_manipulate_controller.js";
import HeroCropController from "../controllers/hero_crop_controller.js";
import ImageSelectorController from "../controllers/image_selector_controller.js";
import ImageUploadController from "../controllers/image_upload_controller.js";

export function registerAdminImagesControllers(stimulus) {
    stimulus.register("admin-index-table", AdminIndexTableController);
    stimulus.register(
        "admin-image-bulk-upload",
        AdminImageBulkUploadController,
    );
    stimulus.register("admin-image-crop-thumb", AdminImageCropThumbController);
    stimulus.register("admin-image-manipulate", AdminImageManipulateController);
    stimulus.register("hero-crop", HeroCropController);
    stimulus.register("image-selector", ImageSelectorController);
    stimulus.register("image-upload", ImageUploadController);
}

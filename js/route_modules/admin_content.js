import BlogPostFormController from "../controllers/blog_post_form_controller.js";
import ImageSelectorController from "../controllers/image_selector_controller.js";

export function registerAdminContentControllers(stimulus) {
    stimulus.register("blog-post-form", BlogPostFormController);
    stimulus.register("image-selector", ImageSelectorController);
}

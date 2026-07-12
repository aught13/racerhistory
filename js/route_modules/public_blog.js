import BlogInteractionsController from "../controllers/blog_interactions_controller.js";

export function registerPublicBlogControllers(stimulus) {
    stimulus.register("blog-interactions", BlogInteractionsController);
}

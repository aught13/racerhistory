import { Controller } from "@hotwired/stimulus";

import initBlogInteractions from "../legacy/modules/blog-interactions.mjs";

function getInitializer() {
    if (typeof window !== "undefined") {
        const override = window.__BLOG_INTERACTIONS_INIT__;
        if (typeof override === "function") {
            return override;
        }
    }

    return initBlogInteractions;
}

export default class extends Controller {
    connect() {
        getInitializer()({ root: this.element });
    }
}

import { Controller } from "@hotwired/stimulus";

import initPersonBlogPopovers from "../legacy/modules/person-blog-popover.mjs";

function getInitializer() {
    if (typeof window !== "undefined") {
        const override = window.__PERSON_BLOG_POPOVER_INIT__;
        if (typeof override === "function") {
            return override;
        }
    }

    return initPersonBlogPopovers;
}

export default class extends Controller {
    connect() {
        getInitializer()({ root: this.element });
    }
}

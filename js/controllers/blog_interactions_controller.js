import { Controller } from "@hotwired/stimulus";

import initBlogInteractions from "../legacy/modules/blog-interactions.mjs";

export default class extends Controller {
    connect() {
        initBlogInteractions({ root: this.element });
    }
}

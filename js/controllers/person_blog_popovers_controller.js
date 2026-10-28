import { Controller } from "@hotwired/stimulus";

import initPersonBlogPopovers from "../legacy/modules/person-blog-popover.mjs";

export default class extends Controller {
    connect() {
        initPersonBlogPopovers({ root: this.element });
    }
}

/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import PersonBlogPopoversController from "../controllers/person_blog_popovers_controller.js";

describe("person-blog-popovers controller", () => {
    let application;

    beforeEach(() => {
        document.body.innerHTML = `
            <div data-controller="person-blog-popovers" data-person-blog-popovers>
                <a data-person-blog-popover data-person-blog-popover-url="/blog/popover/story"></a>
            </div>
        `;

        window.__PERSON_BLOG_POPOVER_INIT__ = jest.fn(() => ({ links: [] }));

        application = Application.start();
        application.register(
            "person-blog-popovers",
            PersonBlogPopoversController,
        );
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        delete window.__PERSON_BLOG_POPOVER_INIT__;
        document.body.innerHTML = "";
        jest.restoreAllMocks();
    });

    test("initializes popovers scoped to the controller root", async () => {
        await Promise.resolve();

        const root = document.querySelector(
            '[data-controller="person-blog-popovers"]',
        );
        expect(window.__PERSON_BLOG_POPOVER_INIT__).toHaveBeenCalledWith({
            root,
        });
    });
});

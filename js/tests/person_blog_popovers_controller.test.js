/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

jest.mock("../legacy/modules/person-blog-popover.mjs", () => ({
    __esModule: true,
    default: jest.fn(() => ({ links: [] })),
}));

import initPersonBlogPopovers from "../legacy/modules/person-blog-popover.mjs";
import PersonBlogPopoversController from "../controllers/person_blog_popovers_controller.js";

describe("person-blog-popovers controller", () => {
    let application;

    beforeEach(() => {
        jest.clearAllMocks();
        document.body.innerHTML = `
            <div data-controller="person-blog-popovers" data-person-blog-popovers>
                <a data-person-blog-popover data-person-blog-popover-url="/blog/popover/story"></a>
            </div>
        `;

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

        document.body.innerHTML = "";
        jest.restoreAllMocks();
    });

    test("initializes popovers scoped to the controller root", async () => {
        await Promise.resolve();

        const root = document.querySelector(
            '[data-controller="person-blog-popovers"]',
        );
        expect(initPersonBlogPopovers).toHaveBeenCalledWith({ root });
    });
});

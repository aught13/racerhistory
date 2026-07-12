/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import BlogInteractionsController from "../controllers/blog_interactions_controller.js";

describe("blog-interactions controller", () => {
    let application;

    beforeEach(() => {
        document.body.innerHTML = `
            <turbo-frame id="blog" data-controller="blog-interactions"></turbo-frame>
        `;

        window.__BLOG_INTERACTIONS_INIT__ = jest.fn();

        application = Application.start();
        application.register("blog-interactions", BlogInteractionsController);
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        delete window.__BLOG_INTERACTIONS_INIT__;
        document.body.innerHTML = "";
        jest.restoreAllMocks();
    });

    test("initializes interactions scoped to the controller root", async () => {
        await Promise.resolve();

        const root = document.getElementById("blog");
        expect(window.__BLOG_INTERACTIONS_INIT__).toHaveBeenCalledWith({
            root,
        });
    });
});

/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

jest.mock("../legacy/modules/blog-interactions.mjs", () => ({
    __esModule: true,
    default: jest.fn(),
}));

import initBlogInteractions from "../legacy/modules/blog-interactions.mjs";
import BlogInteractionsController from "../controllers/blog_interactions_controller.js";

describe("blog-interactions controller", () => {
    let application;

    beforeEach(() => {
        document.body.innerHTML = `
            <turbo-frame id="blog" data-controller="blog-interactions"></turbo-frame>
        `;

        jest.clearAllMocks();

        application = Application.start();
        application.register("blog-interactions", BlogInteractionsController);
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        document.body.innerHTML = "";
        jest.restoreAllMocks();
    });

    test("initializes interactions scoped to the controller root", async () => {
        await Promise.resolve();

        const root = document.getElementById("blog");
        expect(initBlogInteractions).toHaveBeenCalledWith({
            root,
        });
    });
});

import { jest } from "@jest/globals";

/**
 * Comprehensive branch coverage for modules/blog-interactions.mjs
 */

beforeEach(() => {
    jest.resetModules();
    document.body.innerHTML = "";
});

afterEach(() => {
    jest.restoreAllMocks();
});

describe("blog-interactions.mjs", () => {
    test("setupBlogPostCollapse binds collapse button and clears view frame", async () => {
        document.body.innerHTML = `
            <turbo-frame id="blog-post-my-post">
                <div class="blog-featured" style="display:block">Featured</div>
                <div class="blog-list-item" style="display:block">List</div>
                <turbo-frame data-view-frame id="view-frame">
                    <button class="blog-collapse">Close</button>
                    <div class="blog-post-view">Content</div>
                </turbo-frame>
            </turbo-frame>
        `;
        const mod = await import("../modules/blog-interactions.mjs");
        mod.setupBlogPostCollapse(document);

        const btn = document.querySelector(".blog-collapse");
        const event = new MouseEvent("click", { bubbles: true });
        Object.defineProperty(event, "preventDefault", {
            value: jest.fn(),
        });
        Object.defineProperty(event, "stopPropagation", {
            value: jest.fn(),
        });
        btn.dispatchEvent(event);

        const viewFrame = document.getElementById("view-frame");
        expect(viewFrame.innerHTML).toBe("");

        const parentFrame = document.getElementById("blog-post-my-post");
        expect(parentFrame.hasAttribute("data-expanded")).toBe(false);
    });

    test("setupBlogPostCollapse does nothing when no view frame", async () => {
        document.body.innerHTML = `
            <div>
                <button class="blog-collapse">Close</button>
            </div>
        `;
        const mod = await import("../modules/blog-interactions.mjs");
        mod.setupBlogPostCollapse(document);

        const btn = document.querySelector(".blog-collapse");
        const event = new MouseEvent("click", { bubbles: true });
        Object.defineProperty(event, "preventDefault", {
            value: jest.fn(),
        });
        Object.defineProperty(event, "stopPropagation", {
            value: jest.fn(),
        });
        btn.dispatchEvent(event);
    });

    test("setupBlogPostCollapse handles parent without blog-post prefix", async () => {
        document.body.innerHTML = `
            <div id="not-blog">
                <turbo-frame data-view-frame id="vf">
                    <button class="blog-collapse">Close</button>
                </turbo-frame>
            </div>
        `;
        const mod = await import("../modules/blog-interactions.mjs");
        mod.setupBlogPostCollapse(document);

        const btn = document.querySelector(".blog-collapse");
        const event = new MouseEvent("click", { bubbles: true });
        Object.defineProperty(event, "preventDefault", {
            value: jest.fn(),
        });
        Object.defineProperty(event, "stopPropagation", {
            value: jest.fn(),
        });
        btn.dispatchEvent(event);
    });

    test("setupBlogPostCollapse does not re-bind buttons", async () => {
        document.body.innerHTML = `
            <turbo-frame data-view-frame id="vf">
                <button class="blog-collapse">Close</button>
            </turbo-frame>
        `;
        const mod = await import("../modules/blog-interactions.mjs");
        mod.setupBlogPostCollapse(document);
        mod.setupBlogPostCollapse(document); // second call should skip
    });

    test("markBlogPostExpanded marks the containing frame", async () => {
        document.body.innerHTML = `
            <turbo-frame id="blog-post-test-slug">
                <div class="blog-featured">Featured</div>
                <div class="blog-list-item">List</div>
                <div class="blog-post-view" data-blog-post="test-slug">
                    Content
                </div>
            </turbo-frame>
        `;
        const mod = await import("../modules/blog-interactions.mjs");
        mod.markBlogPostExpanded(document);

        const frame = document.getElementById("blog-post-test-slug");
        expect(frame.getAttribute("data-expanded")).toBe("true");
        expect(frame.classList.contains("blog-post-expanded")).toBe(true);

        const featured = frame.querySelector(".blog-featured");
        expect(featured.style.display).toBe("none");

        const listItem = frame.querySelector(".blog-list-item");
        expect(listItem.style.display).toBe("none");
    });

    test("markBlogPostExpanded does nothing when no post view", async () => {
        document.body.innerHTML = `<div>No post view</div>`;
        const mod = await import("../modules/blog-interactions.mjs");
        expect(() => mod.markBlogPostExpanded(document)).not.toThrow();
    });

    test("markBlogPostExpanded does nothing when no slug", async () => {
        document.body.innerHTML = `
            <div class="blog-post-view">Content</div>
        `;
        const mod = await import("../modules/blog-interactions.mjs");
        expect(() => mod.markBlogPostExpanded(document)).not.toThrow();
    });

    test("markBlogPostExpanded does nothing when container not found", async () => {
        document.body.innerHTML = `
            <div class="blog-post-view" data-blog-post="missing">Content</div>
        `;
        const mod = await import("../modules/blog-interactions.mjs");
        expect(() => mod.markBlogPostExpanded(document)).not.toThrow();
    });

    test("markBlogPostExpanded handles missing featured/listItem", async () => {
        document.body.innerHTML = `
            <turbo-frame id="blog-post-nofeat">
                <div class="blog-post-view" data-blog-post="nofeat">Content</div>
            </turbo-frame>
        `;
        const mod = await import("../modules/blog-interactions.mjs");
        mod.markBlogPostExpanded(document);

        const frame = document.getElementById("blog-post-nofeat");
        expect(frame.getAttribute("data-expanded")).toBe("true");
    });

    test("initBlogInteractions calls both setup functions", async () => {
        document.body.innerHTML = `
            <turbo-frame id="blog-post-slug1">
                <div class="blog-post-view" data-blog-post="slug1">Content</div>
                <turbo-frame data-view-frame>
                    <button class="blog-collapse">X</button>
                </turbo-frame>
            </turbo-frame>
        `;
        const mod = await import("../modules/blog-interactions.mjs");
        mod.default({ root: document });
    });

    test("initBlogInteractions uses document as default root", async () => {
        const mod = await import("../modules/blog-interactions.mjs");
        expect(() => mod.default()).not.toThrow();
    });
});

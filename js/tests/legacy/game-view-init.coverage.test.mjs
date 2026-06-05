import { jest } from "@jest/globals";

/**
 * Comprehensive branch coverage tests for modules/game-view-init.mjs
 */

function setupJQueryMock() {
    const dtInstance = {
        destroy: jest.fn(),
        columns: { adjust: jest.fn() },
    };
    const DataTableFn = jest.fn().mockReturnValue(dtInstance);
    DataTableFn.isDataTable = jest.fn().mockReturnValue(false);

    const jq = jest.fn((selector) => {
        const el =
            typeof selector === "string"
                ? document.querySelector(selector)
                : selector;
        return {
            0: el,
            length: el ? 1 : 0,
            get: jest.fn().mockReturnValue(el),
            DataTable: DataTableFn,
        };
    });
    jq.fn = { DataTable: DataTableFn, dataTable: DataTableFn };
    window.$ = jq;
    return { dtInstance, DataTableFn };
}

beforeEach(() => {
    jest.resetModules();
    document.body.innerHTML = "";
    delete window.$;
    delete window.Turbo;
    jest.spyOn(console, "debug").mockImplementation(() => {});
    jest.spyOn(console, "warn").mockImplementation(() => {});
});

afterEach(() => {
    jest.restoreAllMocks();
});

describe("game-view-init.mjs", () => {
    test("initGameView initializes tables with custom selectors", async () => {
        document.body.innerHTML = `
            <table id="game-team-stats-table">
                <thead><tr><th>Player</th><th>PTS</th><th>RB</th></tr></thead>
                <tbody><tr><td>A</td><td>10</td><td>5</td></tr></tbody>
            </table>
            <table id="game-opponent-stats-table">
                <thead><tr><th>Player</th><th>PTS</th></tr></thead>
                <tbody><tr><td>B</td><td>8</td></tr></tbody>
            </table>
        `;
        setupJQueryMock();
        const mod = await import("../../legacy/modules/game-view-init.mjs");
        const result = mod.default();
        expect(result.tables).toHaveLength(2);
    });

    test("initGameView returns empty tables when no tables found", async () => {
        setupJQueryMock();
        const mod = await import("../../legacy/modules/game-view-init.mjs");
        const result = mod.default();
        expect(result.tables).toHaveLength(0);
    });

    test("initGameView handles non-player tables", async () => {
        document.body.innerHTML = `
            <table id="game-custom-table">
                <thead><tr><th>Name</th></tr></thead>
                <tbody><tr><td>X</td></tr></tbody>
            </table>
        `;
        setupJQueryMock();
        const mod = await import("../../legacy/modules/game-view-init.mjs");
        const result = mod.default({
            tableSelectors: ["#game-custom-table"],
        });
        expect(result.tables).toHaveLength(1);
    });

    test("initGameView schedules init when table has no headers", async () => {
        jest.useFakeTimers();
        document.body.innerHTML = `
            <table id="game-team-stats-table"><tbody><tr><td>X</td></tr></tbody></table>
        `;
        setupJQueryMock();
        const mod = await import("../../legacy/modules/game-view-init.mjs");
        const result = mod.default();
        expect(result.tables).toHaveLength(1);
        expect(result.tables[0]).toBeNull();
        jest.useRealTimers();
    });

    test("initGameView returns null when no jQuery", async () => {
        document.body.innerHTML = `
            <table id="game-team-stats-table">
                <thead><tr><th>P</th></tr></thead>
                <tbody><tr><td>X</td></tr></tbody>
            </table>
        `;
        // No jQuery
        const mod = await import("../../legacy/modules/game-view-init.mjs");
        const result = mod.default();
        expect(result.tables).toHaveLength(1);
        expect(result.tables[0]).toBeNull();
    });

    test("initGameView destroys existing DataTable before reinit", async () => {
        document.body.innerHTML = `
            <table id="game-team-stats-table">
                <thead><tr><th>P</th></tr></thead>
                <tbody><tr><td>A</td></tr></tbody>
            </table>
        `;
        const { DataTableFn, dtInstance } = setupJQueryMock();
        DataTableFn.isDataTable.mockReturnValue(true);
        const mod = await import("../../legacy/modules/game-view-init.mjs");
        mod.default();
        expect(dtInstance.destroy).toHaveBeenCalled();
    });

    test("setupBlogClicks handles clicks on blog list items", async () => {
        document.body.innerHTML = `
            <div data-game-blog>
                <div class="blog-list-item" data-blog-post="my-post">
                    <span class="title">Post</span>
                </div>
            </div>
        `;
        setupJQueryMock();

        const navigateSpy = jest.fn();
        window.__RH_NAVIGATE__ = navigateSpy;

        const mod = await import("../../legacy/modules/game-view-init.mjs");
        mod.default({ root: document });

        const item = document.querySelector(".blog-list-item");
        item.querySelector(".title").dispatchEvent(
            new MouseEvent("click", { bubbles: true }),
        );
        expect(navigateSpy).toHaveBeenCalledWith("/blog/my-post");
        delete window.__RH_NAVIGATE__;
    });

    test("setupBlogClicks handles Turbo.visit when available", async () => {
        document.body.innerHTML = `
            <turbo-frame id="blog-frame">
                <div data-game-blog>
                    <div class="blog-list-item" data-blog-post="post-1">
                        <span>Click me</span>
                    </div>
                </div>
                <turbo-frame data-view-frame id="view">View</turbo-frame>
            </turbo-frame>
        `;
        setupJQueryMock();
        window.Turbo = { visit: jest.fn() };

        const mod = await import("../../legacy/modules/game-view-init.mjs");
        mod.default({ root: document });

        const item = document.querySelector(".blog-list-item span");
        item.dispatchEvent(new MouseEvent("click", { bubbles: true }));
        expect(window.Turbo.visit).toHaveBeenCalledWith("/blog/post-1", {
            frame: "view",
        });
    });

    test("setupBlogClicks ignores clicks without slug", async () => {
        document.body.innerHTML = `
            <div data-game-blog>
                <div class="blog-list-item"><span>No slug</span></div>
            </div>
        `;
        setupJQueryMock();
        const mod = await import("../../legacy/modules/game-view-init.mjs");
        mod.default({ root: document });

        const span = document.querySelector("span");
        expect(() =>
            span.dispatchEvent(new MouseEvent("click", { bubbles: true })),
        ).not.toThrow();
    });

    test("setupBlogClicks does not re-bind", async () => {
        document.body.innerHTML = `<div data-game-blog></div>`;
        setupJQueryMock();
        const mod = await import("../../legacy/modules/game-view-init.mjs");
        mod.default({ root: document });
        mod.default({ root: document }); // second call should skip
    });

    test("setupImageGallery opens and closes modal", async () => {
        document.body.innerHTML = `
            <div data-game-image-gallery>
                <img class="game-photo-thumb-img" data-image-id="42" data-image-url="/img/storage/game-42.webp" data-image-filename="test.jpg" />
            </div>
            <div data-game-image-modal>
                <button data-modal-close>X</button>
                <source data-modal-image-webp />
                <img data-modal-image-fallback />
            </div>
        `;
        setupJQueryMock();
        const mod = await import("../../legacy/modules/game-view-init.mjs");
        mod.default({ root: document });

        const thumb = document.querySelector(".game-photo-thumb-img");
        thumb.dispatchEvent(new MouseEvent("click", { bubbles: true }));

        const modal = document.querySelector("[data-game-image-modal]");
        expect(modal.getAttribute("data-modal-open")).toBe("true");

        const modalImg = document.querySelector("[data-modal-image-fallback]");
        expect(modalImg.src).toContain("/img/storage/game-42.webp");

        // Close via button
        const closeBtn = document.querySelector("[data-modal-close]");
        closeBtn.dispatchEvent(new MouseEvent("click", { bubbles: true }));
        expect(modal.hasAttribute("data-modal-open")).toBe(false);
    });

    test("setupImageGallery closes on Escape key", async () => {
        document.body.innerHTML = `
            <div data-game-image-gallery>
                <img class="game-photo-thumb-img" data-image-id="1" data-image-url="/img/storage/game-1.webp" />
            </div>
            <div data-game-image-modal>
                <img data-modal-image-fallback />
            </div>
        `;
        setupJQueryMock();
        const mod = await import("../../legacy/modules/game-view-init.mjs");
        mod.default({ root: document });

        const thumb = document.querySelector(".game-photo-thumb-img");
        thumb.dispatchEvent(new MouseEvent("click", { bubbles: true }));

        const modal = document.querySelector("[data-game-image-modal]");
        expect(modal.getAttribute("data-modal-open")).toBe("true");

        document.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape" }));
        expect(modal.hasAttribute("data-modal-open")).toBe(false);
    });

    test("setupImageGallery closes on backdrop click", async () => {
        document.body.innerHTML = `
            <div data-game-image-gallery>
                <img class="game-photo-thumb-img" data-image-id="5" />
            </div>
            <div data-game-image-modal>
                <img data-modal-image-fallback />
            </div>
        `;
        setupJQueryMock();
        const mod = await import("../../legacy/modules/game-view-init.mjs");
        mod.default({ root: document });

        const thumb = document.querySelector(".game-photo-thumb-img");
        thumb.dispatchEvent(new MouseEvent("click", { bubbles: true }));

        const modal = document.querySelector("[data-game-image-modal]");
        modal.dispatchEvent(new MouseEvent("click", { target: modal }));
    });

    test("setupImageGallery ignores click on non-thumb", async () => {
        document.body.innerHTML = `
            <div data-game-image-gallery>
                <span>Not a thumb</span>
            </div>
            <div data-game-image-modal>
                <img data-modal-image-fallback />
            </div>
        `;
        setupJQueryMock();
        const mod = await import("../../legacy/modules/game-view-init.mjs");
        mod.default({ root: document });

        const span = document.querySelector("span");
        span.dispatchEvent(new MouseEvent("click", { bubbles: true }));
    });

    test("setupImageGallery skips when no gallery or modal", async () => {
        document.body.innerHTML = `<div>No gallery</div>`;
        setupJQueryMock();
        const mod = await import("../../legacy/modules/game-view-init.mjs");
        expect(() => mod.default({ root: document })).not.toThrow();
    });

    test("setupImageGallery ignores click without imageId", async () => {
        document.body.innerHTML = `
            <div data-game-image-gallery>
                <img class="game-photo-thumb-img" />
            </div>
            <div data-game-image-modal>
                <img data-modal-image-fallback />
            </div>
        `;
        setupJQueryMock();
        const mod = await import("../../legacy/modules/game-view-init.mjs");
        mod.default({ root: document });

        const thumb = document.querySelector(".game-photo-thumb-img");
        thumb.dispatchEvent(new MouseEvent("click", { bubbles: true }));
    });
});

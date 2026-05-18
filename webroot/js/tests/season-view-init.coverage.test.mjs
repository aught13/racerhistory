import { jest } from "@jest/globals";

/**
 * Coverage tests for modules/season-view-init.mjs
 * Targets: initTable, scheduleTableInit, setupBlogClicks, safeDivide,
 *   formatPercent, formatInteger, createAdvancedRow, buildAdvancedColumns,
 *   mountAdvancedShootingTable, initSeasonStatsTabs, setupImageGallery
 */

function setupDT() {
    const dtInstance = {
        destroy: jest.fn(),
        draw: jest.fn(),
    };
    const DataTableFn = jest.fn().mockReturnValue(dtInstance);
    DataTableFn.isDataTable = jest.fn().mockReturnValue(false);

    const jq = jest.fn((sel) => {
        if (typeof sel === "string") {
            const el = document.querySelector(sel);
            return {
                length: el ? 1 : 0,
                get: () => el,
                DataTable: DataTableFn,
                remove: jest.fn(),
            };
        }
        return {
            length: 1,
            get: () => sel,
            DataTable: DataTableFn,
        };
    });
    jq.fn = {
        DataTable: DataTableFn,
        dataTable: Object.assign(DataTableFn, {
            isDataTable: DataTableFn.isDataTable,
        }),
    };
    window.$ = jq;
    return { jq, DataTableFn, dtInstance };
}

beforeEach(() => {
    jest.resetModules();
    jest.restoreAllMocks();
    jest.useRealTimers();
    document.body.innerHTML = "";
    delete window.$;
    delete window.Turbo;
});

afterEach(() => {
    jest.restoreAllMocks();
});

describe("season-view-init.mjs (coverage)", () => {
    describe("initTable", () => {
        test("initializes DataTable on table with headers", async () => {
            document.body.innerHTML = `
                <table id="season-games-table"><thead><tr><th>A</th></tr></thead></table>`;
            setupDT();
            const mod = await import("../modules/season-view-init.mjs");
            const result = mod.default({
                tableSelectors: ["#season-games-table"],
            });
            expect(result.tables).toHaveLength(1);
        });

        test("returns null when table is null", async () => {
            document.body.innerHTML = `<div>no table</div>`;
            setupDT();
            const mod = await import("../modules/season-view-init.mjs");
            const result = mod.default({
                tableSelectors: ["#nonexistent"],
            });
            expect(result.tables).toHaveLength(0);
        });

        test("destroys existing DataTable before re-init", async () => {
            document.body.innerHTML = `
                <table id="season-games-table"><thead><tr><th>A</th></tr></thead></table>`;
            const { DataTableFn, dtInstance } = setupDT();
            DataTableFn.isDataTable.mockReturnValue(true);
            const mod = await import("../modules/season-view-init.mjs");
            mod.default({ tableSelectors: ["#season-games-table"] });
            expect(dtInstance.destroy).toHaveBeenCalled();
        });

        test("schedules init when table has no headers", async () => {
            document.body.innerHTML = `
                <table id="season-games-table"><tbody><tr><td>A</td></tr></tbody></table>`;
            setupDT();
            jest.useFakeTimers();
            const mod = await import("../modules/season-view-init.mjs");
            mod.default({ tableSelectors: ["#season-games-table"] });
            // After timeout, should try again
            jest.advanceTimersByTime(100);
        });

        test("does not init without jQuery", async () => {
            document.body.innerHTML = `
                <table id="season-games-table"><thead><tr><th>A</th></tr></thead></table>`;
            // No jQuery
            const mod = await import("../modules/season-view-init.mjs");
            const result = mod.default({
                tableSelectors: ["#season-games-table"],
            });
            expect(result.tables).toHaveLength(1);
            expect(result.tables[0]).toBeNull();
        });
    });

    describe("setupBlogClicks", () => {
        test("binds click handler to blog list items", async () => {
            document.body.innerHTML = `
                <div data-season-blog>
                    <turbo-frame id="blog-frame">
                        <turbo-frame data-view-frame id="view-frame"></turbo-frame>
                        <div class="blog-list-item" data-blog-post="my-post">Post</div>
                    </turbo-frame>
                </div>`;
            setupDT();
            window.Turbo = { visit: jest.fn() };
            const mod = await import("../modules/season-view-init.mjs");
            mod.default({ tableSelectors: [] });

            const item = document.querySelector(".blog-list-item");
            item.click();
            expect(window.Turbo.visit).toHaveBeenCalledWith(
                "/blog/my-post",
                expect.objectContaining({ frame: "view-frame" }),
            );
        });

        test("navigates via location when no view frame", async () => {
            document.body.innerHTML = `
                <div data-season-blog>
                    <div class="blog-list-item" data-blog-post="my-post">Post</div>
                </div>`;
            setupDT();
            const mod = await import("../modules/season-view-init.mjs");
            mod.default({ tableSelectors: [] });

            const item = document.querySelector(".blog-list-item");
            item.click();
            // window.location.href would be set
        });

        test("navigates via location when no Turbo", async () => {
            document.body.innerHTML = `
                <div data-season-blog>
                    <turbo-frame id="blog-frame">
                        <turbo-frame data-view-frame id="vf"></turbo-frame>
                        <div class="blog-list-item" data-blog-post="my-post">Post</div>
                    </turbo-frame>
                </div>`;
            setupDT();
            delete window.Turbo;
            const mod = await import("../modules/season-view-init.mjs");
            mod.default({ tableSelectors: [] });

            const item = document.querySelector(".blog-list-item");
            item.click();
        });

        test("ignores clicks on non-blog items", async () => {
            document.body.innerHTML = `
                <div data-season-blog>
                    <div class="other-item">Not a blog</div>
                </div>`;
            setupDT();
            const mod = await import("../modules/season-view-init.mjs");
            mod.default({ tableSelectors: [] });

            const item = document.querySelector(".other-item");
            item.click();
        });

        test("does not re-bind on already bound root", async () => {
            document.body.innerHTML = `
                <div data-season-blog>
                    <div class="blog-list-item" data-blog-post="p">Post</div>
                </div>`;
            setupDT();
            const mod = await import("../modules/season-view-init.mjs");
            mod.default({ tableSelectors: [] });
            mod.default({ tableSelectors: [] }); // second call
        });

        test("ignores blog item without slug", async () => {
            document.body.innerHTML = `
                <div data-season-blog>
                    <div class="blog-list-item">No slug</div>
                </div>`;
            setupDT();
            const mod = await import("../modules/season-view-init.mjs");
            mod.default({ tableSelectors: [] });

            const item = document.querySelector(".blog-list-item");
            item.click();
        });
    });

    describe("initSeasonStatsTabs", () => {
        test("switches tabs and panels", async () => {
            document.body.innerHTML = `
                <div data-season-stats-tabs>
                    <button data-season-stats-tab="basic" class="active" aria-selected="true">Basic</button>
                    <button data-season-stats-tab="advanced" aria-selected="false">Adv</button>
                    <div data-season-stats-panel="basic" class="active">Basic panel</div>
                    <div data-season-stats-panel="advanced" class="d-none">
                        <div data-season-advanced-table-container></div>
                    </div>
                </div>`;
            setupDT();
            const mod = await import("../modules/season-view-init.mjs");
            mod.default({ tableSelectors: [] });

            // Click advanced tab
            const advBtn = document.querySelector(
                '[data-season-stats-tab="advanced"]',
            );
            advBtn.click();

            expect(advBtn.classList.contains("active")).toBe(true);
            expect(advBtn.getAttribute("aria-selected")).toBe("true");

            const basicBtn = document.querySelector(
                '[data-season-stats-tab="basic"]',
            );
            expect(basicBtn.classList.contains("active")).toBe(false);

            const advPanel = document.querySelector(
                '[data-season-stats-panel="advanced"]',
            );
            expect(advPanel.classList.contains("active")).toBe(true);
        });

        test("handles empty tab target", async () => {
            document.body.innerHTML = `
                <div data-season-stats-tabs>
                    <button data-season-stats-tab="">Empty</button>
                </div>`;
            setupDT();
            const mod = await import("../modules/season-view-init.mjs");
            mod.default({ tableSelectors: [] });

            document.querySelector('[data-season-stats-tab=""]').click();
        });

        test("does nothing when no tabs", async () => {
            document.body.innerHTML = `<div data-season-stats-tabs></div>`;
            setupDT();
            const mod = await import("../modules/season-view-init.mjs");
            mod.default({ tableSelectors: [] });
        });
    });

    describe("mountAdvancedShootingTable", () => {
        test("mounts table with 3-point stats and team totals", async () => {
            const payload = {
                players: [
                    {
                        name: "Player1",
                        GP: 10,
                        FGM: 50,
                        FGA: 100,
                        TPM: 10,
                        TPA: 30,
                        FTM: 20,
                        FTA: 25,
                        PTS: 130,
                    },
                ],
                teamTotals: {
                    name: "Team",
                    GP: 10,
                    FGM: 50,
                    FGA: 100,
                    TPM: 10,
                    TPA: 30,
                    FTM: 20,
                    FTA: 25,
                    PTS: 130,
                },
            };
            document.body.innerHTML = `
                <div data-season-stats-tabs>
                    <button data-season-stats-tab="advanced">Adv</button>
                    <div data-season-stats-panel="advanced"
                         data-season-advanced-stats='${JSON.stringify(payload)}'>
                        <div data-season-advanced-table-container></div>
                    </div>
                </div>`;
            setupDT();
            const mod = await import("../modules/season-view-init.mjs");
            mod.default({ tableSelectors: [] });

            // Click to trigger mount
            document
                .querySelector('[data-season-stats-tab="advanced"]')
                .click();

            const container = document.querySelector(
                "[data-season-advanced-table-container]",
            );
            expect(container.querySelector("table")).toBeTruthy();
            expect(container.querySelector("tfoot")).toBeTruthy();

            // Verify headers include 3-point columns
            const headers = Array.from(
                container.querySelectorAll("thead th"),
            ).map((th) => th.textContent);
            expect(headers).toContain("TP%");
            expect(headers).toContain("eFG%");
            expect(headers).toContain("2PM");
        });

        test("mounts table without 3-point stats", async () => {
            const payload = {
                players: [
                    {
                        name: "Player1",
                        GP: 10,
                        FGM: 50,
                        FGA: 100,
                        TPM: 0,
                        TPA: 0,
                        FTM: 20,
                        FTA: 25,
                        PTS: 120,
                    },
                ],
            };
            document.body.innerHTML = `
                <div data-season-stats-tabs>
                    <button data-season-stats-tab="advanced">Adv</button>
                    <div data-season-stats-panel="advanced"
                         data-season-advanced-stats='${JSON.stringify(payload)}'>
                        <div data-season-advanced-table-container></div>
                    </div>
                </div>`;
            setupDT();
            const mod = await import("../modules/season-view-init.mjs");
            mod.default({ tableSelectors: [] });

            document
                .querySelector('[data-season-stats-tab="advanced"]')
                .click();

            const container = document.querySelector(
                "[data-season-advanced-table-container]",
            );
            const headers = Array.from(
                container.querySelectorAll("thead th"),
            ).map((th) => th.textContent);
            expect(headers).not.toContain("TP%");
            expect(headers).not.toContain("eFG%");
            expect(headers).not.toContain("2PM");
            expect(headers).toContain("TS%");
        });

        test("handles empty players array", async () => {
            const payload = { players: [] };
            document.body.innerHTML = `
                <div data-season-stats-tabs>
                    <button data-season-stats-tab="advanced">Adv</button>
                    <div data-season-stats-panel="advanced"
                         data-season-advanced-stats='${JSON.stringify(payload)}'>
                        <div data-season-advanced-table-container></div>
                    </div>
                </div>`;
            setupDT();
            const mod = await import("../modules/season-view-init.mjs");
            mod.default({ tableSelectors: [] });

            document
                .querySelector('[data-season-stats-tab="advanced"]')
                .click();

            const container = document.querySelector(
                "[data-season-advanced-table-container]",
            );
            expect(container.textContent).toContain("unavailable");
        });

        test("handles invalid JSON payload", async () => {
            document.body.innerHTML = `
                <div data-season-stats-tabs>
                    <button data-season-stats-tab="advanced">Adv</button>
                    <div data-season-stats-panel="advanced"
                         data-season-advanced-stats='invalid{json'>
                        <div data-season-advanced-table-container></div>
                    </div>
                </div>`;
            setupDT();
            const mod = await import("../modules/season-view-init.mjs");
            mod.default({ tableSelectors: [] });

            document
                .querySelector('[data-season-stats-tab="advanced"]')
                .click();

            const container = document.querySelector(
                "[data-season-advanced-table-container]",
            );
            expect(container.textContent).toContain("could not be loaded");
        });

        test("handles no data attribute", async () => {
            document.body.innerHTML = `
                <div data-season-stats-tabs>
                    <button data-season-stats-tab="advanced">Adv</button>
                    <div data-season-stats-panel="advanced">
                        <div data-season-advanced-table-container></div>
                    </div>
                </div>`;
            setupDT();
            const mod = await import("../modules/season-view-init.mjs");
            mod.default({ tableSelectors: [] });

            document
                .querySelector('[data-season-stats-tab="advanced"]')
                .click();
        });

        test("handles no container element", async () => {
            document.body.innerHTML = `
                <div data-season-stats-tabs>
                    <button data-season-stats-tab="advanced">Adv</button>
                    <div data-season-stats-panel="advanced"
                         data-season-advanced-stats='{"players":[]}'>
                    </div>
                </div>`;
            setupDT();
            const mod = await import("../modules/season-view-init.mjs");
            mod.default({ tableSelectors: [] });

            document
                .querySelector('[data-season-stats-tab="advanced"]')
                .click();
        });

        test("already rendered panel skips re-mount", async () => {
            const payload = {
                players: [
                    {
                        name: "P",
                        GP: 1,
                        FGM: 1,
                        FGA: 2,
                        TPM: 0,
                        TPA: 0,
                        FTM: 1,
                        FTA: 1,
                        PTS: 3,
                    },
                ],
            };
            document.body.innerHTML = `
                <div data-season-stats-tabs>
                    <button data-season-stats-tab="advanced">Adv</button>
                    <div data-season-stats-panel="advanced"
                         data-season-advanced-stats='${JSON.stringify(payload)}'
                         data-season-advanced-rendered="true">
                        <div data-season-advanced-table-container></div>
                    </div>
                </div>`;
            setupDT();
            const mod = await import("../modules/season-view-init.mjs");
            mod.default({ tableSelectors: [] });

            document
                .querySelector('[data-season-stats-tab="advanced"]')
                .click();
        });

        test("player with zero FGA for edge case coverage", async () => {
            const payload = {
                players: [
                    {
                        name: "Zero",
                        GP: 0,
                        FGM: 0,
                        FGA: 0,
                        TPM: 0,
                        TPA: 0,
                        FTM: 0,
                        FTA: 0,
                        PTS: 0,
                    },
                ],
            };
            document.body.innerHTML = `
                <div data-season-stats-tabs>
                    <button data-season-stats-tab="advanced">Adv</button>
                    <div data-season-stats-panel="advanced"
                         data-season-advanced-stats='${JSON.stringify(payload)}'>
                        <div data-season-advanced-table-container></div>
                    </div>
                </div>`;
            setupDT();
            const mod = await import("../modules/season-view-init.mjs");
            mod.default({ tableSelectors: [] });

            document
                .querySelector('[data-season-stats-tab="advanced"]')
                .click();

            const cells = document.querySelectorAll("tbody td");
            // All percentage columns should show "—"
            const values = Array.from(cells).map((td) => td.textContent);
            expect(values).toContain("—");
        });
    });

    describe("setupImageGallery", () => {
        test("opens modal on thumb click and sets sources", async () => {
            document.body.innerHTML = `
                <div data-season-image-gallery>
                    <img class="season-photo-thumb-img" data-image-id="42" data-image-filename="photo.jpg" />
                </div>
                <div data-season-image-modal>
                    <button data-modal-close>X</button>
                    <picture>
                        <source data-modal-image-webp />
                        <img data-modal-image-fallback />
                    </picture>
                </div>`;
            setupDT();
            const mod = await import("../modules/season-view-init.mjs");
            mod.default({ tableSelectors: [] });

            const thumb = document.querySelector(".season-photo-thumb-img");
            thumb.click();

            const modal = document.querySelector("[data-season-image-modal]");
            expect(modal.getAttribute("data-modal-open")).toBe("true");

            const img = document.querySelector("[data-modal-image-fallback]");
            expect(img.src).toContain("/images/serve/42");

            const webp = document.querySelector("[data-modal-image-webp]");
            expect(webp.srcset).toContain("/images/serve/42?format=webp");
        });

        test("closes modal on close button click", async () => {
            document.body.innerHTML = `
                <div data-season-image-gallery>
                    <img class="season-photo-thumb-img" data-image-id="1" />
                </div>
                <div data-season-image-modal data-modal-open="true">
                    <button data-modal-close>X</button>
                    <img data-modal-image-fallback />
                </div>`;
            setupDT();
            const mod = await import("../modules/season-view-init.mjs");
            mod.default({ tableSelectors: [] });

            const closeBtn = document.querySelector("[data-modal-close]");
            closeBtn.click();

            const modal = document.querySelector("[data-season-image-modal]");
            expect(modal.hasAttribute("data-modal-open")).toBe(false);
        });

        test("closes modal on background click", async () => {
            document.body.innerHTML = `
                <div data-season-image-gallery>
                    <img class="season-photo-thumb-img" data-image-id="1" />
                </div>
                <div data-season-image-modal data-modal-open="true">
                    <img data-modal-image-fallback />
                </div>`;
            setupDT();
            const mod = await import("../modules/season-view-init.mjs");
            mod.default({ tableSelectors: [] });

            const modal = document.querySelector("[data-season-image-modal]");
            modal.click();
            expect(modal.hasAttribute("data-modal-open")).toBe(false);
        });

        test("closes modal on Escape key", async () => {
            document.body.innerHTML = `
                <div data-season-image-gallery>
                    <img class="season-photo-thumb-img" data-image-id="1" />
                </div>
                <div data-season-image-modal data-modal-open="true">
                    <img data-modal-image-fallback />
                </div>`;
            setupDT();
            const mod = await import("../modules/season-view-init.mjs");
            mod.default({ tableSelectors: [] });

            document.dispatchEvent(
                new KeyboardEvent("keydown", { key: "Escape" }),
            );
            const modal = document.querySelector("[data-season-image-modal]");
            expect(modal.hasAttribute("data-modal-open")).toBe(false);
        });

        test("Escape key does nothing when modal not open", async () => {
            document.body.innerHTML = `
                <div data-season-image-gallery>
                    <img class="season-photo-thumb-img" data-image-id="1" />
                </div>
                <div data-season-image-modal>
                    <img data-modal-image-fallback />
                </div>`;
            setupDT();
            const mod = await import("../modules/season-view-init.mjs");
            mod.default({ tableSelectors: [] });

            document.dispatchEvent(
                new KeyboardEvent("keydown", { key: "Escape" }),
            );
        });

        test("ignores click on non-thumb in gallery", async () => {
            document.body.innerHTML = `
                <div data-season-image-gallery>
                    <div class="other-element">Not a thumb</div>
                </div>
                <div data-season-image-modal>
                    <img data-modal-image-fallback />
                </div>`;
            setupDT();
            const mod = await import("../modules/season-view-init.mjs");
            mod.default({ tableSelectors: [] });

            document.querySelector(".other-element").click();
        });

        test("ignores thumb without image-id", async () => {
            document.body.innerHTML = `
                <div data-season-image-gallery>
                    <img class="season-photo-thumb-img" />
                </div>
                <div data-season-image-modal>
                    <img data-modal-image-fallback />
                </div>`;
            setupDT();
            const mod = await import("../modules/season-view-init.mjs");
            mod.default({ tableSelectors: [] });

            document.querySelector(".season-photo-thumb-img").click();
            const modal = document.querySelector("[data-season-image-modal]");
            expect(modal.hasAttribute("data-modal-open")).toBe(false);
        });

        test("does nothing when no gallery or modal", async () => {
            document.body.innerHTML = `<div>No gallery</div>`;
            setupDT();
            const mod = await import("../modules/season-view-init.mjs");
            mod.default({ tableSelectors: [] });
        });

        test("handles missing modalImg", async () => {
            document.body.innerHTML = `
                <div data-season-image-gallery></div>
                <div data-season-image-modal></div>`;
            setupDT();
            const mod = await import("../modules/season-view-init.mjs");
            mod.default({ tableSelectors: [] });
        });

        test("handles thumb without webp source element", async () => {
            document.body.innerHTML = `
                <div data-season-image-gallery>
                    <img class="season-photo-thumb-img" data-image-id="5" data-image-filename="test.jpg" />
                </div>
                <div data-season-image-modal>
                    <img data-modal-image-fallback />
                </div>`;
            setupDT();
            const mod = await import("../modules/season-view-init.mjs");
            mod.default({ tableSelectors: [] });

            document.querySelector(".season-photo-thumb-img").click();
            const modal = document.querySelector("[data-season-image-modal]");
            expect(modal.getAttribute("data-modal-open")).toBe("true");
            const img = document.querySelector("[data-modal-image-fallback]");
            expect(img.alt).toBe("test.jpg");
        });
    });

    describe("default initSeasonView", () => {
        test("uses default selectors", async () => {
            document.body.innerHTML = `
                <table id="season-games-table"><thead><tr><th>A</th></tr></thead></table>
                <table id="season-roster-table"><thead><tr><th>B</th></tr></thead></table>
                <table id="season-stats-table"><thead><tr><th>C</th></tr></thead></table>`;
            setupDT();
            const mod = await import("../modules/season-view-init.mjs");
            const result = mod.default();
            expect(result.tables).toHaveLength(3);
        });

        test("uses custom root", async () => {
            document.body.innerHTML = `
                <div id="custom-root">
                    <table id="season-games-table"><thead><tr><th>A</th></tr></thead></table>
                </div>`;
            setupDT();
            const mod = await import("../modules/season-view-init.mjs");
            const root = document.getElementById("custom-root");
            const result = mod.default({
                root,
                tableSelectors: ["#season-games-table"],
            });
            expect(result.tables).toHaveLength(1);
        });
    });

    describe("initDeferredImages", () => {
        test("loads images when IntersectionObserver unavailable", async () => {
            const origIO = window.IntersectionObserver;
            delete window.IntersectionObserver;

            document.body.innerHTML = `
                <img class="js-person-thumb" data-thumb-src="/images/serve/1?variant=thumb" src="" alt="Test">
                <img class="js-person-thumb" data-thumb-src="/images/serve/2?variant=thumb" src="" alt="Test2">`;

            const mod = await import("../modules/season-view-init.mjs");
            mod.initDeferredImages(document);

            const imgs = document.querySelectorAll("[data-thumb-src]");
            expect(imgs.length).toBe(2);
            // src is set immediately when no IntersectionObserver
            imgs.forEach((img) => expect(img.src).not.toBe(""));

            window.IntersectionObserver = origIO;
        });

        test("skips already-loaded images", async () => {
            document.body.innerHTML = `
                <img class="js-person-thumb" data-thumb-loaded="1" src="/loaded.jpg" alt="loaded">`;

            const mod = await import("../modules/season-view-init.mjs");
            // Should return early — no error and img unchanged
            mod.initDeferredImages(document);

            const img = document.querySelector("img");
            expect(img.src).toContain("/loaded.jpg");
        });

        test("returns early when no deferred images present", async () => {
            document.body.innerHTML = `<div><p>No images</p></div>`;
            const mod = await import("../modules/season-view-init.mjs");
            // Should not throw
            expect(() => mod.initDeferredImages(document)).not.toThrow();
        });

        test("marks image loaded after successful load event", async () => {
            const origIO = window.IntersectionObserver;
            delete window.IntersectionObserver;

            document.body.innerHTML = `
                <img class="js-season-photo" data-thumb-src="/images/serve/5?w=240" src="" alt="photo">`;

            const mod = await import("../modules/season-view-init.mjs");
            mod.initDeferredImages(document);

            const img = document.querySelector("img");
            img.dispatchEvent(new Event("load"));

            expect(img.dataset.thumbLoaded).toBe("1");
            expect(img.hasAttribute("data-thumb-src")).toBe(false);

            window.IntersectionObserver = origIO;
        });

        test("retries once after error event", async () => {
            const origIO = window.IntersectionObserver;
            delete window.IntersectionObserver;

            jest.useFakeTimers();
            document.body.innerHTML = `
                <img class="js-person-thumb" data-thumb-src="/images/serve/9?variant=thumb" src="" alt="retry">`;

            const mod = await import("../modules/season-view-init.mjs");
            mod.initDeferredImages(document);

            const img = document.querySelector("img");
            img.dispatchEvent(new Event("error"));

            expect(img.dataset.thumbRetried).toBe("1");

            // Advance timer to trigger the retry
            jest.advanceTimersByTime(900);

            window.IntersectionObserver = origIO;
        });

        test("uses IntersectionObserver when available", async () => {
            const observedEls = [];
            const observerCb = jest.fn();
            const mockObserver = {
                observe: jest.fn((el) => observedEls.push(el)),
                unobserve: jest.fn(),
                disconnect: jest.fn(),
            };
            window.IntersectionObserver = jest.fn().mockImplementation((cb) => {
                observerCb.mockImplementation(cb);
                return mockObserver;
            });

            document.body.innerHTML = `
                <img class="js-person-thumb" data-thumb-src="/images/serve/3?variant=thumb" src="" alt="a">
                <img class="js-season-photo" data-thumb-src="/images/serve/4?w=240" src="" alt="b">`;

            const mod = await import("../modules/season-view-init.mjs");
            mod.initDeferredImages(document);

            expect(mockObserver.observe).toHaveBeenCalledTimes(2);

            // Simulate intersection for first image
            const img = document.querySelector(".js-person-thumb");
            observerCb([{ isIntersecting: true, target: img }]);
            expect(mockObserver.unobserve).toHaveBeenCalledWith(img);
            expect(img.src).not.toBe("");
        });

        test("called by initSeasonView default export", async () => {
            const origIO = window.IntersectionObserver;
            delete window.IntersectionObserver;

            document.body.innerHTML = `
                <img class="js-person-thumb" data-thumb-src="/images/serve/7?variant=thumb" src="" alt="via-init">`;

            setupDT();
            const mod = await import("../modules/season-view-init.mjs");
            mod.default({ tableSelectors: [] });

            const img = document.querySelector("img");
            expect(img.src).not.toBe("");

            window.IntersectionObserver = origIO;
        });
    });
});

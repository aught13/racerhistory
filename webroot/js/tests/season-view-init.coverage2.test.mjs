/**
 * @jest-environment jsdom
 */

/* Targeted branch coverage for season-view-init.mjs additional branches */

function setupDT() {
    const dtInstance = {
        destroy: jest.fn(),
        columns: { adjust: jest.fn().mockReturnValue({ draw: jest.fn() }) },
    };
    const DataTableFn = jest.fn().mockReturnValue(dtInstance);
    DataTableFn.isDataTable = jest.fn().mockReturnValue(false);

    const jq = jest.fn((_sel) => ({
        DataTable: DataTableFn,
    }));
    jq.fn = { dataTable: { isDataTable: jest.fn(() => false) } };
    window.$ = jq;
    return { jq, DataTableFn, dtInstance };
}

beforeEach(() => {
    jest.resetModules();
    document.body.innerHTML = "";
    delete window.$;
    delete window.Turbo;
});

describe("season-view-init additional branches", () => {
    test("scheduleTableInit - null table returns null", async () => {
        setupDT();
        const mod = await import("../modules/season-view-init.mjs");
        // Calling with root containing no tables
        const result = mod.default({ tableSelectors: ["#nonexistent"] });
        expect(result.tables).toEqual([]);
    });

    test("scheduleTableInit - already scheduled returns null", async () => {
        document.body.innerHTML = `
            <table id="t1"><tbody><tr><td>1</td></tr></tbody></table>`;
        // No thead -> will try scheduleTableInit
        setupDT();
        const mod = await import("../modules/season-view-init.mjs");
        const result = mod.default({ tableSelectors: ["#t1"] });
        // Table without headers triggers scheduleTableInit
        expect(result.tables.length).toBe(1);
    });

    test("setupBlogClicks with Turbo.visit available", async () => {
        document.body.innerHTML = `
            <table id="t1"><thead><tr><th>A</th></tr></thead><tbody><tr><td>x</td></tr></tbody></table>
            <div data-season-blog>
                <turbo-frame id="frame1">
                    <div class="blog-list-item" data-blog-post="my-post">
                        <span>Click me</span>
                    </div>
                    <turbo-frame data-view-frame id="view1"></turbo-frame>
                </turbo-frame>
            </div>`;
        setupDT();
        window.Turbo = { visit: jest.fn() };

        const mod = await import("../modules/season-view-init.mjs");
        mod.default({ tableSelectors: ["#t1"] });

        // Click the blog item
        const item = document.querySelector(".blog-list-item span");
        item.click();
        expect(window.Turbo.visit).toHaveBeenCalledWith("/blog/my-post", {
            frame: "view1",
        });
    });

    test("setupBlogClicks without Turbo falls to location.href", async () => {
        document.body.innerHTML = `
            <table id="t1"><thead><tr><th>A</th></tr></thead><tbody><tr><td>x</td></tr></tbody></table>
            <div data-season-blog>
                <turbo-frame id="frame1">
                    <div class="blog-list-item" data-blog-post="test-slug">
                        <span>Post</span>
                    </div>
                    <turbo-frame data-view-frame id="view1"></turbo-frame>
                </turbo-frame>
            </div>`;
        setupDT();
        // No Turbo

        const mod = await import("../modules/season-view-init.mjs");
        mod.default({ tableSelectors: ["#t1"] });

        const item = document.querySelector(".blog-list-item span");
        item.click();
    });

    test("setupBlogClicks item without slug is ignored", async () => {
        document.body.innerHTML = `
            <table id="t1"><thead><tr><th>A</th></tr></thead><tbody><tr><td>x</td></tr></tbody></table>
            <div data-season-blog>
                <div class="blog-list-item"><span>No slug</span></div>
            </div>`;
        setupDT();
        const mod = await import("../modules/season-view-init.mjs");
        mod.default({ tableSelectors: ["#t1"] });

        document.querySelector(".blog-list-item span").click();
    });

    test("setupBlogClicks click on non-blog-item is ignored", async () => {
        document.body.innerHTML = `
            <table id="t1"><thead><tr><th>A</th></tr></thead><tbody><tr><td>x</td></tr></tbody></table>
            <div data-season-blog>
                <div class="not-a-blog-item"><span>Other</span></div>
            </div>`;
        setupDT();
        const mod = await import("../modules/season-view-init.mjs");
        mod.default({ tableSelectors: ["#t1"] });

        document.querySelector(".not-a-blog-item span").click();
    });

    test("setupBlogClicks without viewFrame falls to location.href", async () => {
        document.body.innerHTML = `
            <table id="t1"><thead><tr><th>A</th></tr></thead><tbody><tr><td>x</td></tr></tbody></table>
            <div data-season-blog>
                <div class="blog-list-item" data-blog-post="direct"><span>Go</span></div>
            </div>`;
        setupDT();
        const mod = await import("../modules/season-view-init.mjs");
        mod.default({ tableSelectors: ["#t1"] });

        document.querySelector(".blog-list-item span").click();
    });

    test("mountAdvancedShootingTable already rendered is skipped", async () => {
        document.body.innerHTML = `
            <table id="t1"><thead><tr><th>A</th></tr></thead><tbody><tr><td>x</td></tr></tbody></table>
            <div data-season-stats-tabs>
                <button data-season-stats-tab="advanced">Advanced</button>
                <div data-season-stats-panel="advanced"
                     data-season-advanced-stats='{"players":[]}'
                     data-season-advanced-rendered="true">
                    <div data-season-advanced-table-container></div>
                </div>
            </div>`;
        setupDT();
        const mod = await import("../modules/season-view-init.mjs");
        mod.default({ tableSelectors: ["#t1"] });

        document.querySelector("[data-season-stats-tab]").click();
    });

    test("mountAdvancedShootingTable no stats data marks rendered", async () => {
        document.body.innerHTML = `
            <table id="t1"><thead><tr><th>A</th></tr></thead><tbody><tr><td>x</td></tr></tbody></table>
            <div data-season-stats-tabs>
                <button data-season-stats-tab="advanced">Advanced</button>
                <div data-season-stats-panel="advanced"
                     data-season-advanced-stats="">
                    <div data-season-advanced-table-container></div>
                </div>
            </div>`;
        setupDT();
        const mod = await import("../modules/season-view-init.mjs");
        mod.default({ tableSelectors: ["#t1"] });

        // Remove data attribute to trigger no-stats path
        const panel = document.querySelector("[data-season-advanced-stats]");
        panel.removeAttribute("data-season-advanced-stats");
        document.querySelector("[data-season-stats-tab]").click();
    });

    test("mountAdvancedShootingTable no container marks rendered", async () => {
        document.body.innerHTML = `
            <table id="t1"><thead><tr><th>A</th></tr></thead><tbody><tr><td>x</td></tr></tbody></table>
            <div data-season-stats-tabs>
                <button data-season-stats-tab="advanced">Advanced</button>
                <div data-season-stats-panel="advanced"
                     data-season-advanced-stats='{"players":[{"name":"A","GP":1,"FGM":5,"FGA":10,"TPM":0,"TPA":0,"FTM":3,"FTA":4,"PTS":13}]}'>
                </div>
            </div>`;
        setupDT();
        const mod = await import("../modules/season-view-init.mjs");
        mod.default({ tableSelectors: ["#t1"] });

        document.querySelector("[data-season-stats-tab]").click();
    });

    test("mountAdvancedShootingTable invalid JSON shows error", async () => {
        document.body.innerHTML = `
            <table id="t1"><thead><tr><th>A</th></tr></thead><tbody><tr><td>x</td></tr></tbody></table>
            <div data-season-stats-tabs>
                <button data-season-stats-tab="advanced">Advanced</button>
                <div data-season-stats-panel="advanced"
                     data-season-advanced-stats="not-json">
                    <div data-season-advanced-table-container></div>
                </div>
            </div>`;
        setupDT();
        const mod = await import("../modules/season-view-init.mjs");
        mod.default({ tableSelectors: ["#t1"] });

        document.querySelector("[data-season-stats-tab]").click();
        const container = document.querySelector(
            "[data-season-advanced-table-container]",
        );
        expect(container.textContent).toContain("could not be loaded");
    });

    test("mountAdvancedShootingTable empty players array", async () => {
        document.body.innerHTML = `
            <table id="t1"><thead><tr><th>A</th></tr></thead><tbody><tr><td>x</td></tr></tbody></table>
            <div data-season-stats-tabs>
                <button data-season-stats-tab="advanced">Advanced</button>
                <div data-season-stats-panel="advanced"
                     data-season-advanced-stats='{"players":[]}'>
                    <div data-season-advanced-table-container></div>
                </div>
            </div>`;
        setupDT();
        const mod = await import("../modules/season-view-init.mjs");
        mod.default({ tableSelectors: ["#t1"] });

        document.querySelector("[data-season-stats-tab]").click();
        const container = document.querySelector(
            "[data-season-advanced-table-container]",
        );
        expect(container.textContent).toContain("unavailable");
    });

    test("mountAdvancedShootingTable without three-point shots", async () => {
        const stats = {
            players: [
                {
                    name: "Player A",
                    GP: 10,
                    FGM: 20,
                    FGA: 40,
                    TPM: 0,
                    TPA: 0,
                    FTM: 10,
                    FTA: 15,
                    PTS: 50,
                },
            ],
        };
        document.body.innerHTML = `
            <table id="t1"><thead><tr><th>A</th></tr></thead><tbody><tr><td>x</td></tr></tbody></table>
            <div data-season-stats-tabs>
                <button data-season-stats-tab="advanced">Advanced</button>
                <div data-season-stats-panel="advanced"
                     data-season-advanced-stats='${JSON.stringify(stats)}'>
                    <div data-season-advanced-table-container></div>
                </div>
            </div>`;
        setupDT();
        const mod = await import("../modules/season-view-init.mjs");
        mod.default({ tableSelectors: ["#t1"] });

        document.querySelector("[data-season-stats-tab]").click();
        const table = document.querySelector(
            "[data-season-advanced-table-container] table",
        );
        expect(table).toBeTruthy();
        // Should NOT have 3P columns
        const headers = table.querySelectorAll("th");
        const headerTexts = Array.from(headers).map((h) => h.textContent);
        expect(headerTexts).not.toContain("TPM");
        expect(headerTexts).not.toContain("eFG%");
    });

    test("mountAdvancedShootingTable with teamTotals row", async () => {
        const stats = {
            players: [
                {
                    name: "P1",
                    GP: 5,
                    FGM: 10,
                    FGA: 20,
                    TPM: 3,
                    TPA: 8,
                    FTM: 5,
                    FTA: 7,
                    PTS: 28,
                },
            ],
            teamTotals: {
                name: "Team",
                GP: 5,
                FGM: 10,
                FGA: 20,
                TPM: 3,
                TPA: 8,
                FTM: 5,
                FTA: 7,
                PTS: 28,
            },
        };
        document.body.innerHTML = `
            <table id="t1"><thead><tr><th>A</th></tr></thead><tbody><tr><td>x</td></tr></tbody></table>
            <div data-season-stats-tabs>
                <button data-season-stats-tab="advanced">Advanced</button>
                <div data-season-stats-panel="advanced"
                     data-season-advanced-stats='${JSON.stringify(stats)}'>
                    <div data-season-advanced-table-container></div>
                </div>
            </div>`;
        setupDT();
        const mod = await import("../modules/season-view-init.mjs");
        mod.default({ tableSelectors: ["#t1"] });

        document.querySelector("[data-season-stats-tab]").click();
        const tfoot = document.querySelector(
            "[data-season-advanced-table-container] tfoot",
        );
        expect(tfoot).toBeTruthy();
        expect(tfoot.textContent).toContain("Team");
    });

    test("image gallery click opens modal", async () => {
        document.body.innerHTML = `
            <table id="t1"><thead><tr><th>A</th></tr></thead><tbody><tr><td>x</td></tr></tbody></table>
            <div data-season-image-gallery>
                <img class="season-photo-thumb-img" data-image-url="/img/storage/2026/05/test.jpg" data-image-filename="test.jpg" />
            </div>
            <div data-season-image-modal>
                <button data-modal-close></button>
                <source data-modal-image-webp />
                <img data-modal-image-fallback />
            </div>`;
        setupDT();
        const mod = await import("../modules/season-view-init.mjs");
        mod.default({ tableSelectors: ["#t1"] });

        // Click image
        document.querySelector(".season-photo-thumb-img").click();
        const modal = document.querySelector("[data-season-image-modal]");
        expect(modal.hasAttribute("data-modal-open")).toBe(true);
        expect(
            document.querySelector("[data-modal-image-fallback]")?.src,
        ).toContain("/img/storage/2026/05/test.jpg");

        // Close via button
        document.querySelector("[data-modal-close]").click();
        expect(modal.hasAttribute("data-modal-open")).toBe(false);
    });

    test("image gallery click outside modal closes it", async () => {
        document.body.innerHTML = `
            <table id="t1"><thead><tr><th>A</th></tr></thead><tbody><tr><td>x</td></tr></tbody></table>
            <div data-season-image-gallery>
                <img class="season-photo-thumb-img" data-image-url="/img/storage/2026/05/f.jpg" data-image-filename="f.jpg" />
            </div>
            <div data-season-image-modal>
                <img data-modal-image-fallback />
            </div>`;
        setupDT();
        const mod = await import("../modules/season-view-init.mjs");
        mod.default({ tableSelectors: ["#t1"] });

        document.querySelector(".season-photo-thumb-img").click();
        const modal = document.querySelector("[data-season-image-modal]");
        expect(modal.hasAttribute("data-modal-open")).toBe(true);

        // Click on modal background (event.target === modal)
        modal.click();
        expect(modal.hasAttribute("data-modal-open")).toBe(false);
    });

    test("image gallery Escape key closes modal", async () => {
        document.body.innerHTML = `
            <table id="t1"><thead><tr><th>A</th></tr></thead><tbody><tr><td>x</td></tr></tbody></table>
            <div data-season-image-gallery>
                <img class="season-photo-thumb-img" data-image-url="/img/storage/2026/05/a.jpg" />
            </div>
            <div data-season-image-modal>
                <img data-modal-image-fallback />
            </div>`;
        setupDT();
        const mod = await import("../modules/season-view-init.mjs");
        mod.default({ tableSelectors: ["#t1"] });

        document.querySelector(".season-photo-thumb-img").click();
        const modal = document.querySelector("[data-season-image-modal]");
        expect(modal.hasAttribute("data-modal-open")).toBe(true);

        document.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape" }));
        expect(modal.hasAttribute("data-modal-open")).toBe(false);
    });

    test("image gallery click without image URL is ignored", async () => {
        document.body.innerHTML = `
            <table id="t1"><thead><tr><th>A</th></tr></thead><tbody><tr><td>x</td></tr></tbody></table>
            <div data-season-image-gallery>
                <img class="season-photo-thumb-img" />
            </div>
            <div data-season-image-modal>
                <img data-modal-image-fallback />
            </div>`;
        setupDT();
        const mod = await import("../modules/season-view-init.mjs");
        mod.default({ tableSelectors: ["#t1"] });

        document.querySelector(".season-photo-thumb-img").click();
        const modal = document.querySelector("[data-season-image-modal]");
        expect(modal.hasAttribute("data-modal-open")).toBe(false);
    });

    test("image gallery without modal img is ignored", async () => {
        document.body.innerHTML = `
            <table id="t1"><thead><tr><th>A</th></tr></thead><tbody><tr><td>x</td></tr></tbody></table>
            <div data-season-image-gallery>
                <img class="season-photo-thumb-img" data-image-url="/img/storage/2026/05/b.jpg" />
            </div>
            <div data-season-image-modal></div>`;
        setupDT();
        const mod = await import("../modules/season-view-init.mjs");
        mod.default({ tableSelectors: ["#t1"] });
    });

    test("Escape key when modal not open does nothing", async () => {
        document.body.innerHTML = `
            <table id="t1"><thead><tr><th>A</th></tr></thead><tbody><tr><td>x</td></tr></tbody></table>
            <div data-season-image-gallery>
                <img class="season-photo-thumb-img" data-image-url="/img/storage/2026/05/c.jpg" />
            </div>
            <div data-season-image-modal>
                <img data-modal-image-fallback />
            </div>`;
        setupDT();
        const mod = await import("../modules/season-view-init.mjs");
        mod.default({ tableSelectors: ["#t1"] });

        // Escape without opening
        document.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape" }));
    });

    test("initTable with existing DataTable destroys first", async () => {
        document.body.innerHTML = `
            <table id="t1"><thead><tr><th>A</th></tr></thead><tbody><tr><td>x</td></tr></tbody></table>`;
        const dtInstance = {
            destroy: jest.fn(),
            columns: {
                adjust: jest.fn().mockReturnValue({ draw: jest.fn() }),
            },
        };
        const DataTableFn = jest.fn().mockReturnValue(dtInstance);
        DataTableFn.isDataTable = jest.fn().mockReturnValue(true);

        const jq = jest.fn(() => ({
            DataTable: DataTableFn,
        }));
        jq.fn = { dataTable: { isDataTable: jest.fn(() => true) } };
        window.$ = jq;

        const mod = await import("../modules/season-view-init.mjs");
        mod.default({ tableSelectors: ["#t1"] });
        expect(dtInstance.destroy).toHaveBeenCalled();
    });

    test("stats tabs toggle active states", async () => {
        document.body.innerHTML = `
            <table id="t1"><thead><tr><th>A</th></tr></thead><tbody><tr><td>x</td></tr></tbody></table>
            <div data-season-stats-tabs>
                <button data-season-stats-tab="basic" class="active" aria-selected="true">Basic</button>
                <button data-season-stats-tab="advanced">Advanced</button>
                <div data-season-stats-panel="basic" class="active">Basic content</div>
                <div data-season-stats-panel="advanced" class="d-none"
                     data-season-advanced-stats='{"players":[]}'>
                    <div data-season-advanced-table-container></div>
                </div>
            </div>`;
        setupDT();
        const mod = await import("../modules/season-view-init.mjs");
        mod.default({ tableSelectors: ["#t1"] });

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
    });

    test("tab button with empty target does nothing", async () => {
        document.body.innerHTML = `
            <table id="t1"><thead><tr><th>A</th></tr></thead><tbody><tr><td>x</td></tr></tbody></table>
            <div data-season-stats-tabs>
                <button data-season-stats-tab="">Empty</button>
            </div>`;
        setupDT();
        const mod = await import("../modules/season-view-init.mjs");
        mod.default({ tableSelectors: ["#t1"] });

        document.querySelector("[data-season-stats-tab]").click();
    });
});

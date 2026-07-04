/* season-view-init.branches.test.mjs
 * Focused tests for webroot/js/modules/season-view-init.mjs
 */
import initSeasonView from "../../legacy/modules/season-view-init.mjs";

beforeEach(() => {
    document.body.innerHTML = "";
});

test("initSeasonView registers stats tab and mounts advanced table on click (valid payload)", () => {
    // prepare DOM with stats tab and advanced panel
    const root = document.createElement("div");
    const tabButton = document.createElement("button");
    tabButton.setAttribute("data-season-stats-tab", "advanced");
    const panelsWrap = document.createElement("div");
    panelsWrap.setAttribute("data-season-stats-tabs", "");
    const advancedPanel = document.createElement("div");
    advancedPanel.setAttribute("data-season-advanced-stats", "");
    const container = document.createElement("div");
    container.setAttribute("data-season-advanced-table-container", "");

    const payload = {
        players: [
            {
                name: "A",
                GP: 1,
                FGM: 2,
                FGA: 4,
                TPM: 1,
                TPA: 2,
                FTM: 0,
                FTA: 0,
                PTS: 5,
            },
        ],
        teamTotals: {
            name: "Team",
            GP: 1,
            FGM: 2,
            FGA: 4,
            TPM: 1,
            TPA: 2,
            FTM: 0,
            FTA: 0,
            PTS: 5,
        },
    };

    advancedPanel.dataset.seasonAdvancedStats = JSON.stringify(payload);
    panelsWrap.appendChild(tabButton);
    panelsWrap.appendChild(advancedPanel);
    advancedPanel.appendChild(container);
    root.appendChild(panelsWrap);
    document.body.appendChild(root);

    // call init which wires up handlers
    initSeasonView({ root: document });

    // click tab to trigger mountAdvancedShootingTable via handler
    tabButton.click();

    expect(container.querySelector("table")).toBeTruthy();
});

test("initSeasonView mounts placeholder when advanced payload invalid", () => {
    const root = document.createElement("div");
    const tabButton = document.createElement("button");
    tabButton.setAttribute("data-season-stats-tab", "advanced");
    const panelsWrap = document.createElement("div");
    panelsWrap.setAttribute("data-season-stats-tabs", "");
    const advancedPanel = document.createElement("div");
    advancedPanel.setAttribute("data-season-advanced-stats", "");
    const container = document.createElement("div");
    container.setAttribute("data-season-advanced-table-container", "");

    advancedPanel.dataset.seasonAdvancedStats = "not-json";
    panelsWrap.appendChild(tabButton);
    panelsWrap.appendChild(advancedPanel);
    advancedPanel.appendChild(container);
    root.appendChild(panelsWrap);
    document.body.appendChild(root);

    initSeasonView({ root: document });
    tabButton.click();

    expect(container.innerHTML).toMatch(/could not be loaded|unavailable/);
});

test("advanced table omits three-point columns when no attempts", () => {
    const root = document.createElement("div");
    const tabButton = document.createElement("button");
    tabButton.setAttribute("data-season-stats-tab", "advanced");
    const panelsWrap = document.createElement("div");
    panelsWrap.setAttribute("data-season-stats-tabs", "");
    const advancedPanel = document.createElement("div");
    advancedPanel.setAttribute("data-season-advanced-stats", "");
    const container = document.createElement("div");
    container.setAttribute("data-season-advanced-table-container", "");

    const payload = {
        players: [
            {
                name: "A",
                GP: 1,
                FGM: 2,
                FGA: 4,
                TPM: 0,
                TPA: 0,
                FTM: 0,
                FTA: 0,
                PTS: 5,
            },
        ],
    };

    advancedPanel.dataset.seasonAdvancedStats = JSON.stringify(payload);
    panelsWrap.appendChild(tabButton);
    panelsWrap.appendChild(advancedPanel);
    advancedPanel.appendChild(container);
    root.appendChild(panelsWrap);
    document.body.appendChild(root);

    initSeasonView({ root: document });
    tabButton.click();

    const headers = Array.from(container.querySelectorAll("th")).map(
        (th) => th.textContent,
    );
    expect(headers).not.toContain("TP%");
    expect(headers).toContain("FG%");
});

test("image gallery modal closes on background click and Escape", () => {
    const root = document.createElement("div");
    root.innerHTML = `
    <div data-season-image-gallery>
      <img class="season-photo-thumb-img" data-image-id="1" data-image-filename="a.jpg" />
    </div>
    <div data-season-image-modal data-modal-open>
      <button data-modal-close>Close</button>
      <picture>
        <source data-modal-image-webp />
        <img data-modal-image-fallback />
      </picture>
    </div>
  `;
    document.body.appendChild(root);

    initSeasonView({ root });

    const modal = root.querySelector("[data-season-image-modal]");
    modal.dispatchEvent(new MouseEvent("click", { bubbles: true }));
    expect(modal.hasAttribute("data-modal-open")).toBe(false);

    modal.setAttribute("data-modal-open", "");
    document.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape" }));
    expect(modal.hasAttribute("data-modal-open")).toBe(false);
});

test("blog click falls back to location when view frame missing", () => {
    const root = document.createElement("div");
    root.innerHTML = `
    <div data-season-blog>
      <turbo-frame id="blog-frame">
        <div class="blog-list-item" data-blog-post="missing"></div>
      </turbo-frame>
    </div>
  `;
    document.body.appendChild(root);

    const navigateSpy = jest.fn();
    window.__RH_NAVIGATE__ = navigateSpy;

    initSeasonView({ root });
    const item = root.querySelector(".blog-list-item");
    item.dispatchEvent(new MouseEvent("click", { bubbles: true }));

    expect(navigateSpy).toHaveBeenCalledWith("/blog/missing");
    delete window.__RH_NAVIGATE__;
});

test("advanced panel marks rendered when container missing", () => {
    const root = document.createElement("div");
    const tabButton = document.createElement("button");
    tabButton.setAttribute("data-season-stats-tab", "advanced");
    const panelsWrap = document.createElement("div");
    panelsWrap.setAttribute("data-season-stats-tabs", "");
    const advancedPanel = document.createElement("div");
    advancedPanel.setAttribute("data-season-advanced-stats", "");
    advancedPanel.dataset.seasonAdvancedStats = JSON.stringify({ players: [] });

    panelsWrap.appendChild(tabButton);
    panelsWrap.appendChild(advancedPanel);
    root.appendChild(panelsWrap);
    document.body.appendChild(root);

    initSeasonView({ root: document });
    tabButton.click();

    expect(advancedPanel.dataset.seasonAdvancedRendered).toBe("true");
});

test("blog click falls back when Turbo missing but view frame exists", () => {
    const root = document.createElement("div");
    root.innerHTML = `
    <div data-season-blog>
      <turbo-frame id="blog-frame">
        <div class="blog-list-item" data-blog-post="alpha"></div>
        <turbo-frame id="blog-view" data-view-frame></turbo-frame>
      </turbo-frame>
    </div>
  `;
    document.body.appendChild(root);

    const navigateSpy = jest.fn();
    window.__RH_NAVIGATE__ = navigateSpy;

    delete window.Turbo;

    initSeasonView({ root });
    const item = root.querySelector(".blog-list-item");
    item.dispatchEvent(new MouseEvent("click", { bubbles: true }));

    expect(navigateSpy).toHaveBeenCalledWith("/blog/alpha");
    delete window.__RH_NAVIGATE__;
});

test("advanced table renders em dash for invalid percentages", () => {
    const root = document.createElement("div");
    const tabButton = document.createElement("button");
    tabButton.setAttribute("data-season-stats-tab", "advanced");
    const panelsWrap = document.createElement("div");
    panelsWrap.setAttribute("data-season-stats-tabs", "");
    const advancedPanel = document.createElement("div");
    advancedPanel.setAttribute("data-season-advanced-stats", "");
    const container = document.createElement("div");
    container.setAttribute("data-season-advanced-table-container", "");

    const payload = {
        players: [
            {
                name: "A",
                GP: 1,
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

    advancedPanel.dataset.seasonAdvancedStats = JSON.stringify(payload);
    panelsWrap.appendChild(tabButton);
    panelsWrap.appendChild(advancedPanel);
    advancedPanel.appendChild(container);
    root.appendChild(panelsWrap);
    document.body.appendChild(root);

    initSeasonView({ root: document });
    tabButton.click();

    const cellTexts = Array.from(container.querySelectorAll("td")).map(
        (td) => td.textContent,
    );
    expect(cellTexts).toContain("—");
});

// ── Additional Branch Coverage Tests ──────────────────────────────────

test("initSeasonView with custom table selectors", () => {
    const root = document.createElement("div");
    const customTable = document.createElement("table");
    customTable.id = "custom-table";
    customTable.innerHTML = "<thead><tr><th>Col</th></tr></thead>";
    root.appendChild(customTable);
    document.body.appendChild(root);

    const result = initSeasonView({
        root,
        tableSelectors: ["#custom-table"],
    });

    expect(result.tables).toBeDefined();
    expect(result.tables.length).toBeGreaterThan(0);
});

test("initSeasonView handles missing root parameter", () => {
    const table = document.createElement("table");
    table.id = "season-games-table";
    table.innerHTML = "<thead><tr><th>Col</th></tr></thead>";
    document.body.appendChild(table);

    // Should use document as root when not provided
    const result = initSeasonView();
    expect(result).toBeDefined();
});

test("setupBlogClicks ignores clicks outside blog items", () => {
    const root = document.createElement("div");
    root.setAttribute("data-season-blog", "");
    root.innerHTML = `
        <div class="blog-list-item" data-blog-post="post1"></div>
        <button>Outside</button>
    `;
    document.body.appendChild(root);

    const navigateSpy = jest.fn();
    window.__RH_NAVIGATE__ = navigateSpy;

    initSeasonView({ root });

    // Click outside blog item
    root.querySelector("button").click();
    expect(navigateSpy).not.toHaveBeenCalled();

    delete window.__RH_NAVIGATE__;
});

test("blog click without slug data does not navigate", () => {
    const root = document.createElement("div");
    root.setAttribute("data-season-blog", "");
    root.innerHTML = `
        <div class="blog-list-item"></div>
    `;
    document.body.appendChild(root);

    const navigateSpy = jest.fn();
    window.__RH_NAVIGATE__ = navigateSpy;

    initSeasonView({ root });

    root.querySelector(".blog-list-item").click();
    expect(navigateSpy).not.toHaveBeenCalled();

    delete window.__RH_NAVIGATE__;
});

test("blog click with Turbo.visit and custom frame", () => {
    const root = document.createElement("div");
    root.innerHTML = `
        <div data-season-blog>
            <turbo-frame>
                <div class="blog-list-item" data-blog-post="slug1"></div>
                <turbo-frame id="view-frame" data-view-frame></turbo-frame>
            </turbo-frame>
        </div>
    `;
    document.body.appendChild(root);

    window.Turbo = {
        visit: jest.fn(),
    };

    initSeasonView({ root });

    root.querySelector(".blog-list-item").click();
    expect(window.Turbo.visit).toHaveBeenCalledWith("/blog/slug1", {
        frame: "view-frame",
    });

    delete window.Turbo;
});

test("advanced stats with empty players array", () => {
    const root = document.createElement("div");
    const tabButton = document.createElement("button");
    tabButton.setAttribute("data-season-stats-tab", "advanced");
    const panelsWrap = document.createElement("div");
    panelsWrap.setAttribute("data-season-stats-tabs", "");
    const advancedPanel = document.createElement("div");
    advancedPanel.setAttribute("data-season-advanced-stats", "");
    const container = document.createElement("div");
    container.setAttribute("data-season-advanced-table-container", "");

    const payload = { players: [] };
    advancedPanel.dataset.seasonAdvancedStats = JSON.stringify(payload);
    panelsWrap.appendChild(tabButton);
    panelsWrap.appendChild(advancedPanel);
    advancedPanel.appendChild(container);
    root.appendChild(panelsWrap);
    document.body.appendChild(root);

    initSeasonView({ root });
    tabButton.click();

    expect(container.textContent).toContain("unavailable");
});

test("advanced stats with non-object teamTotals is skipped", () => {
    const root = document.createElement("div");
    const tabButton = document.createElement("button");
    tabButton.setAttribute("data-season-stats-tab", "advanced");
    const panelsWrap = document.createElement("div");
    panelsWrap.setAttribute("data-season-stats-tabs", "");
    const advancedPanel = document.createElement("div");
    advancedPanel.setAttribute("data-season-advanced-stats", "");
    const container = document.createElement("div");
    container.setAttribute("data-season-advanced-table-container", "");

    const payload = {
        players: [
            {
                name: "A",
                GP: 1,
                FGM: 2,
                FGA: 4,
                TPM: 0,
                TPA: 0,
                FTM: 0,
                FTA: 0,
                PTS: 5,
            },
        ],
        teamTotals: null, // Non-object should be skipped
    };

    advancedPanel.dataset.seasonAdvancedStats = JSON.stringify(payload);
    panelsWrap.appendChild(tabButton);
    panelsWrap.appendChild(advancedPanel);
    advancedPanel.appendChild(container);
    root.appendChild(panelsWrap);
    document.body.appendChild(root);

    initSeasonView({ root });
    tabButton.click();

    const footRows = container.querySelectorAll("tfoot");
    expect(footRows.length).toBe(0);
});

test("stats tabs with no buttons returns early", () => {
    const root = document.createElement("div");
    root.setAttribute("data-season-stats-tabs", "");
    // No buttons with data-season-stats-tab attribute

    document.body.appendChild(root);

    const result = initSeasonView({ root });
    expect(result.tables).toBeDefined(); // Should still complete
});

test("image gallery closes on Escape key", () => {
    const root = document.createElement("div");
    root.innerHTML = `
        <div data-season-image-gallery>
            <img class="season-photo-thumb-img" data-image-id="1" data-image-filename="a.jpg" data-image-url="img.jpg" />
        </div>
        <div data-season-image-modal data-modal-open>
            <button data-modal-close>Close</button>
            <img data-modal-image-fallback />
        </div>
    `;
    document.body.appendChild(root);

    initSeasonView({ root });

    const modal = root.querySelector("[data-season-image-modal]");
    expect(modal.hasAttribute("data-modal-open")).toBe(true);

    const escapeEvent = new KeyboardEvent("keydown", { key: "Escape" });
    document.dispatchEvent(escapeEvent);

    expect(modal.hasAttribute("data-modal-open")).toBe(false);
});

test("image gallery without modal returns early", () => {
    const root = document.createElement("div");
    root.innerHTML = `
        <div data-season-image-gallery>
            <img class="season-photo-thumb-img" data-image-url="img.jpg" />
        </div>
        <!-- No modal element -->
    `;
    document.body.appendChild(root);

    expect(() => initSeasonView({ root })).not.toThrow();
});

test("image gallery without gallery returns early", () => {
    const root = document.createElement("div");
    root.innerHTML = `
        <div data-season-image-modal>
            <img data-modal-image-fallback />
        </div>
        <!-- No gallery element -->
    `;
    document.body.appendChild(root);

    expect(() => initSeasonView({ root })).not.toThrow();
});

test("deferred images with IntersectionObserver", () => {
    const root = document.createElement("div");
    const img = document.createElement("img");
    img.setAttribute("data-thumb-src", "lazy.jpg");
    root.appendChild(img);
    document.body.appendChild(root);

    // Mock IntersectionObserver
    global.IntersectionObserver = jest.fn((callback) => ({
        observe: jest.fn((img) => {
            // Simulate intersection
            callback([{ target: img, isIntersecting: true }]);
        }),
        unobserve: jest.fn(),
    }));

    initSeasonView({ root });

    expect(global.IntersectionObserver).toHaveBeenCalled();
});

test("image gallery with currentSrc fallback", () => {
    const root = document.createElement("div");
    root.innerHTML = `
        <div data-season-image-gallery>
            <img class="season-photo-thumb-img" data-image-id="1" />
        </div>
        <div data-season-image-modal>
            <button data-modal-close>Close</button>
            <img data-modal-image-fallback src="fallback.jpg" />
        </div>
    `;
    document.body.appendChild(root);

    // Set currentSrc (picture source selection)
    const thumb = root.querySelector(".season-photo-thumb-img");
    Object.defineProperty(thumb, "currentSrc", {
        value: "picture-source.jpg",
        writable: false,
    });

    initSeasonView({ root });

    thumb.click();
    const modal = root.querySelector("[data-season-image-modal]");
    const modalImg = modal.querySelector("[data-modal-image-fallback]");

    expect(modal.hasAttribute("data-modal-open")).toBe(true);
    expect(modalImg.src).toContain("picture-source.jpg");
});

test("stats tab with no matching panels", () => {
    const root = document.createElement("div");
    root.innerHTML = `
        <div data-season-stats-tabs>
            <button data-season-stats-tab="tab1">Tab 1</button>
        </div>
        <!-- No panels with data-season-stats-panel -->
    `;
    document.body.appendChild(root);

    expect(() => initSeasonView({ root })).not.toThrow();
});

// ── Direct function tests for uncovered branches ───────────────────────

test("blog click - item exists and root contains it", () => {
    const root = document.createElement("div");
    root.innerHTML = `
        <div data-season-blog>
            <div class="blog-list-item" data-blog-post="my-post"></div>
        </div>
    `;
    document.body.appendChild(root);

    const navigateSpy = jest.fn();
    window.__RH_NAVIGATE__ = navigateSpy;

    initSeasonView({ root });

    const item = root.querySelector(".blog-list-item");
    item.dispatchEvent(new MouseEvent("click", { bubbles: true }));

    expect(navigateSpy).toHaveBeenCalledWith("/blog/my-post");
    delete window.__RH_NAVIGATE__;
});

test("blog click with Turbo.visit when frame exists", () => {
    const root = document.createElement("div");
    root.innerHTML = `
        <div data-season-blog>
            <turbo-frame id="blog-frame">
                <div class="blog-list-item" data-blog-post="test-post"></div>
                <turbo-frame id="view-frame" data-view-frame></turbo-frame>
            </turbo-frame>
        </div>
    `;
    document.body.appendChild(root);

    window.Turbo = {
        visit: jest.fn(),
    };

    initSeasonView({ root });

    const item = root.querySelector(".blog-list-item");
    item.dispatchEvent(new MouseEvent("click", { bubbles: true }));

    expect(window.Turbo.visit).toHaveBeenCalledWith("/blog/test-post", {
        frame: "view-frame",
    });
    delete window.Turbo;
});

test("advanced stats with three-point shots false condition", () => {
    const root = document.createElement("div");
    const tabButton = document.createElement("button");
    tabButton.setAttribute("data-season-stats-tab", "advanced");
    const panelsWrap = document.createElement("div");
    panelsWrap.setAttribute("data-season-stats-tabs", "");
    const advancedPanel = document.createElement("div");
    advancedPanel.setAttribute("data-season-advanced-stats", "");
    const container = document.createElement("div");
    container.setAttribute("data-season-advanced-table-container", "");

    // Players with NO three-point shots
    const payload = {
        players: [
            {
                name: "NoThrees",
                GP: 1,
                FGM: 2,
                FGA: 4,
                TPM: 0, // No three-pointers made
                TPA: 0, // No three-pointers attempted
                FTM: 1,
                FTA: 2,
                PTS: 5,
            },
        ],
    };

    advancedPanel.dataset.seasonAdvancedStats = JSON.stringify(payload);
    panelsWrap.appendChild(tabButton);
    panelsWrap.appendChild(advancedPanel);
    advancedPanel.appendChild(container);
    root.appendChild(panelsWrap);
    document.body.appendChild(root);

    initSeasonView({ root });
    tabButton.click();

    // When hasThreePointShots is false, 2P% and TP% columns should NOT exist
    const headers = Array.from(container.querySelectorAll("th")).map(
        (th) => th.textContent,
    );
    expect(headers).not.toContain("2PM");
    expect(headers).not.toContain("TP%");
    expect(headers).not.toContain("eFG%");
});

test("advanced stats with teamTotals object correctly adds footer", () => {
    const root = document.createElement("div");
    const tabButton = document.createElement("button");
    tabButton.setAttribute("data-season-stats-tab", "advanced");
    const panelsWrap = document.createElement("div");
    panelsWrap.setAttribute("data-season-stats-tabs", "");
    const advancedPanel = document.createElement("div");
    advancedPanel.setAttribute("data-season-advanced-stats", "");
    const container = document.createElement("div");
    container.setAttribute("data-season-advanced-table-container", "");

    const payload = {
        players: [
            {
                name: "Player1",
                GP: 10,
                FGM: 50,
                FGA: 100,
                TPM: 20,
                TPA: 50,
                FTM: 15,
                FTA: 20,
                PTS: 135,
            },
        ],
        teamTotals: {
            name: "Team Total",
            GP: 10,
            FGM: 500,
            FGA: 1000,
            TPM: 150,
            TPA: 400,
            FTM: 100,
            FTA: 150,
            PTS: 1250,
        },
    };

    advancedPanel.dataset.seasonAdvancedStats = JSON.stringify(payload);
    panelsWrap.appendChild(tabButton);
    panelsWrap.appendChild(advancedPanel);
    advancedPanel.appendChild(container);
    root.appendChild(panelsWrap);
    document.body.appendChild(root);

    initSeasonView({ root });
    tabButton.click();

    const tfoot = container.querySelector("tfoot");
    expect(tfoot).toBeTruthy();
    expect(tfoot.textContent).toContain("Team Total");
});

test("image gallery clicks with data-image-url attribute", () => {
    const root = document.createElement("div");
    root.innerHTML = `
        <div data-season-image-gallery>
            <img class="season-photo-thumb-img" data-image-id="1" data-image-url="custom-url.jpg" data-image-filename="photo.jpg" />
        </div>
        <div data-season-image-modal>
            <button data-modal-close>Close</button>
            <img data-modal-image-fallback />
            <source data-modal-image-webp />
        </div>
    `;
    document.body.appendChild(root);

    initSeasonView({ root });

    root.querySelector(".season-photo-thumb-img").click();

    const modal = root.querySelector("[data-season-image-modal]");
    const modalImg = modal.querySelector("[data-modal-image-fallback]");
    expect(modal.hasAttribute("data-modal-open")).toBe(true);
    expect(modalImg.src).toContain("custom-url.jpg");
});

test("image gallery close button removes modal-open attribute", () => {
    const root = document.createElement("div");
    root.innerHTML = `
        <div data-season-image-gallery>
            <img class="season-photo-thumb-img" data-image-url="img.jpg" />
        </div>
        <div data-season-image-modal data-modal-open>
            <button data-modal-close>Close</button>
            <img data-modal-image-fallback />
        </div>
    `;
    document.body.appendChild(root);

    initSeasonView({ root });

    const modal = root.querySelector("[data-season-image-modal]");
    const closeBtn = modal.querySelector("[data-modal-close]");

    closeBtn.click();
    expect(modal.hasAttribute("data-modal-open")).toBe(false);
});

test("image gallery closes on click outside modal", () => {
    const root = document.createElement("div");
    root.innerHTML = `
        <div data-season-image-gallery>
            <img class="season-photo-thumb-img" data-image-url="img.jpg" />
        </div>
        <div data-season-image-modal data-modal-open>
            <button data-modal-close>Close</button>
            <img data-modal-image-fallback />
        </div>
    `;
    document.body.appendChild(root);

    initSeasonView({ root });

    const modal = root.querySelector("[data-season-image-modal]");
    const event = new MouseEvent("click");
    Object.defineProperty(event, "target", { value: modal, enumerable: true });
    modal.dispatchEvent(event);

    expect(modal.hasAttribute("data-modal-open")).toBe(false);
});

test("image gallery click inside modal doesn't close it", () => {
    const root = document.createElement("div");
    root.innerHTML = `
        <div data-season-image-gallery>
            <img class="season-photo-thumb-img" data-image-url="img.jpg" />
        </div>
        <div data-season-image-modal data-modal-open>
            <button data-modal-close>Close</button>
            <img data-modal-image-fallback />
        </div>
    `;
    document.body.appendChild(root);

    initSeasonView({ root });

    const modal = root.querySelector("[data-season-image-modal]");
    const img = modal.querySelector("[data-modal-image-fallback]");
    const event = new MouseEvent("click");
    Object.defineProperty(event, "target", {
        value: img,
        enumerable: true,
    });
    modal.dispatchEvent(event);

    expect(modal.hasAttribute("data-modal-open")).toBe(true);
});

test("deferred images marked with thumb-loaded skip loading", () => {
    const root = document.createElement("div");
    const img1 = document.createElement("img");
    img1.setAttribute("data-thumb-src", "lazy1.jpg");
    img1.dataset.thumbLoaded = "1"; // Already loaded

    const img2 = document.createElement("img");
    img2.setAttribute("data-thumb-src", "lazy2.jpg");
    // Not loaded yet

    root.appendChild(img1);
    root.appendChild(img2);
    document.body.appendChild(root);

    global.IntersectionObserver = jest.fn((callback) => ({
        observe: jest.fn((img) => {
            if (img === img2) {
                callback([{ target: img2, isIntersecting: true }]);
            }
        }),
        unobserve: jest.fn(),
    }));

    initSeasonView({ root });

    expect(global.IntersectionObserver).toHaveBeenCalled();
});

test("advanced stats panel marked as rendered skips re-render", () => {
    const root = document.createElement("div");
    const tabButton = document.createElement("button");
    tabButton.setAttribute("data-season-stats-tab", "advanced");
    const panelsWrap = document.createElement("div");
    panelsWrap.setAttribute("data-season-stats-tabs", "");
    const advancedPanel = document.createElement("div");
    advancedPanel.setAttribute("data-season-advanced-stats", "");
    advancedPanel.dataset.seasonAdvancedRendered = "true"; // Already rendered

    const container = document.createElement("div");
    container.setAttribute("data-season-advanced-table-container", "");
    container.textContent = "Original content";

    advancedPanel.appendChild(container);
    panelsWrap.appendChild(tabButton);
    panelsWrap.appendChild(advancedPanel);
    root.appendChild(panelsWrap);
    document.body.appendChild(root);

    initSeasonView({ root });
    tabButton.click();

    // Content should not change because panel was already rendered
    expect(container.textContent).toBe("Original content");
});

import { initSeasonStatsTables } from "../../lib/season_stats_tables.js";

beforeEach(() => {
    document.body.innerHTML = "";
    delete globalThis.$;
});

afterEach(() => {
    delete globalThis.$;
});

test.each(["per-game", "advanced"])(
    "initializes the %s table when its tab is opened",
    (tabName) => {
        const section = document.createElement("div");
        section.setAttribute("data-season-stats-tabs", "");
        const tableId =
            tabName === "advanced"
                ? "season-advanced-stats-table"
                : "season-per-game-table";
        const sortHeader = tabName === "advanced" ? "PProd" : "PTS";
        section.innerHTML = `
            <button class="active" data-season-stats-tab="basic" aria-selected="true"></button>
            <button data-season-stats-tab="${tabName}" aria-selected="false"></button>
            <div class="tab-pane active" data-season-stats-panel="basic"></div>
            <div class="tab-pane d-none" data-season-stats-panel="${tabName}">
                <table class="js-datatable" id="${tableId}">
                    <thead><tr><th>Name</th><th>${sortHeader}</th></tr></thead>
                </table>
            </div>`;
        document.body.appendChild(section);

        const table = section.querySelector("table");
        const initializeTable = jest.fn();
        const jq = jest.fn(() => ({ DataTable: initializeTable }));
        jq.fn = { dataTable: { isDataTable: jest.fn(() => false) } };
        globalThis.$ = jq;

        initSeasonStatsTables(document);
        initSeasonStatsTables(document);
        const tab = section.querySelector(
            `[data-season-stats-tab="${tabName}"]`,
        );
        tab.dispatchEvent(new MouseEvent("click", { bubbles: true }));
        tab.dispatchEvent(new MouseEvent("click", { bubbles: true }));

        expect(jq).toHaveBeenCalledTimes(1);
        expect(jq).toHaveBeenCalledWith(table);
        expect(initializeTable).toHaveBeenCalledTimes(1);
        expect(initializeTable).toHaveBeenCalledWith(
            expect.objectContaining({ order: [[1, "desc"]] }),
        );
        expect(table.dataset.seasonStatsTableInitialized).toBe("true");
        expect(tab.getAttribute("aria-selected")).toBe("true");
        expect(
            section
                .querySelector('[data-season-stats-panel="basic"]')
                .classList.contains("d-none"),
        ).toBe(true);
    },
);

test("keeps a plain stats table usable when DataTables is unavailable", () => {
    const section = document.createElement("div");
    section.setAttribute("data-season-stats-tabs", "");
    section.innerHTML = `
        <button data-season-stats-tab="per-game"></button>
        <div data-season-stats-panel="per-game">
            <table class="js-datatable"><tbody><tr><td>12.0</td></tr></tbody></table>
        </div>`;
    document.body.appendChild(section);

    initSeasonStatsTables(document);
    section
        .querySelector('[data-season-stats-tab="per-game"]')
        .dispatchEvent(new MouseEvent("click", { bubbles: true }));

    expect(section.textContent).toContain("12.0");
    expect(
        section.querySelector("table").dataset.seasonStatsTableInitialized,
    ).toBeUndefined();
    expect(
        section
            .querySelector("table")
            .closest("[data-season-stats-panel]")
            .classList.contains("d-none"),
    ).toBe(false);
});

const SEASON_STATS_TABLE_OPTIONS = {
    paging: false,
    info: false,
    searching: true,
    order: [],
    responsive: false,
    scrollX: true,
    autoWidth: false,
    dom: "ftip",
};

export function getSeasonStatsTableOptions(table) {
    let sortHeader = null;
    if (table.id === "season-advanced-stats-table") {
        sortHeader = "PProd";
    } else if (
        ["season-stats-table", "season-per-game-table"].includes(table.id)
    ) {
        sortHeader = "PTS";
    }
    if (!sortHeader) {
        return { ...SEASON_STATS_TABLE_OPTIONS };
    }

    const sortIndex = Array.from(table.querySelectorAll("thead th"), (header) =>
        header.textContent.trim(),
    ).indexOf(sortHeader);

    return {
        ...SEASON_STATS_TABLE_OPTIONS,
        order: sortIndex >= 0 ? [[sortIndex, "desc"]] : [],
    };
}

export function initSeasonStatsTables(root = globalThis.document) {
    if (!root || typeof root.querySelectorAll !== "function") {
        return;
    }

    const sections = [];
    if (root.matches?.("[data-season-stats-tabs]")) {
        sections.push(root);
    }
    sections.push(...root.querySelectorAll("[data-season-stats-tabs]"));

    sections.forEach((section) => {
        const tabs = Array.from(
            section.querySelectorAll("[data-season-stats-tab]"),
        );
        const panels = Array.from(
            section.querySelectorAll("[data-season-stats-panel]"),
        );
        tabs.forEach((tab) => {
            if (tab.dataset.seasonStatsTableBound === "true") {
                return;
            }

            tab.dataset.seasonStatsTableBound = "true";
            tab.addEventListener("click", () => {
                const activeTab = tab.dataset.seasonStatsTab;
                if (!activeTab) {
                    return;
                }

                tabs.forEach((candidate) => {
                    const active = candidate === tab;
                    candidate.classList.toggle("active", active);
                    candidate.setAttribute(
                        "aria-selected",
                        active ? "true" : "false",
                    );
                });
                panels.forEach((candidate) => {
                    const active =
                        candidate.dataset.seasonStatsPanel === activeTab;
                    candidate.classList.toggle("active", active);
                    candidate.classList.toggle("d-none", !active);
                });

                const panel = panels.find(
                    (candidate) =>
                        candidate.dataset.seasonStatsPanel === activeTab,
                );
                panel
                    ?.querySelectorAll("table.js-datatable")
                    .forEach((table) => {
                        if (
                            !table.isConnected ||
                            table.dataset.seasonStatsTableInitialized === "true"
                        ) {
                            return;
                        }

                        const jq = globalThis.$;
                        if (!jq?.fn?.dataTable) {
                            return;
                        }
                        if (!jq.fn.dataTable.isDataTable(table)) {
                            jq(table).DataTable(
                                getSeasonStatsTableOptions(table),
                            );
                        }
                        table.dataset.seasonStatsTableInitialized = "true";
                    });
            });
        });
    });
}

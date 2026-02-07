const DEFAULT_TABLE_OPTIONS = {
    paging: false,
    info: false,
    searching: true,
    order: [],
    responsive: false,
    scrollX: true,
    autoWidth: false,
    dom: "ftip",
};

const HEADER_CHECK_DELAY = 60;

function hasTableHeaders(table) {
    const headers = table?.querySelectorAll("thead th");
    return Boolean(headers?.length);
}

function scheduleTableInit(table) {
    if (!table || table.dataset.seasonTableInitScheduled === "true") {
        return null;
    }
    table.dataset.seasonTableInitScheduled = "true";
    window.setTimeout(() => {
        table.dataset.seasonTableInitScheduled = "false";
        initTable(table);
    }, HEADER_CHECK_DELAY);
    return null;
}

function initTable(table) {
    const $ = window.$;
    if (!table) {
        return null;
    }
    if (!hasTableHeaders(table)) {
        return scheduleTableInit(table);
    }
    if (!table || !$ || !$.fn || !$.fn.dataTable) {
        return null;
    }

    if ($.fn.dataTable.isDataTable(table)) {
        try {
            $(table).DataTable().destroy();
        } catch {
            // ignore
        }
    }

    return $(table).DataTable(DEFAULT_TABLE_OPTIONS);
}

function setupBlogClicks(root) {
    if (!root || root.dataset.blogRootBound === "true") {
        return;
    }
    root.dataset.blogRootBound = "true";
    root.addEventListener("click", (event) => {
        const target = event?.target instanceof Element ? event.target : null;
        const item = target?.closest(".blog-list-item");
        if (!item || !root.contains(item)) {
            return;
        }
        const slug = item.dataset.blogPost;
        if (!slug) return;
        const container = item.closest("turbo-frame");
        const viewFrame = container?.querySelector(
            "turbo-frame[data-view-frame]",
        );
        if (!viewFrame) {
            window.location.href = `/blog/${slug}`;
            return;
        }
        if (window.Turbo && typeof window.Turbo.visit === "function") {
            window.Turbo.visit(`/blog/${slug}`, { frame: viewFrame.id });
        } else {
            window.location.href = `/blog/${slug}`;
        }
    });
}

export default function initSeasonView(options = {}) {
    const selectors = options.tableSelectors || [
        "#season-games-table",
        "#season-roster-table",
        "#season-stats-table",
    ];
    const root = options.root || document;

    const tables = selectors
        .map((selector) => root.querySelector(selector))
        .filter(Boolean)
        .map((table) => initTable(table));

    root.querySelectorAll("[data-season-blog]").forEach((section) => {
        setupBlogClicks(section);
    });

    return { tables };
}

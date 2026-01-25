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

function initTable(table) {
    const $ = window.$;
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
    if (!root) {
        return;
    }
    const items = root.querySelectorAll(".blog-list-item");
    items.forEach((item) => {
        if (item.dataset.blogBound === "true") {
            return;
        }
        item.dataset.blogBound = "true";
        item.addEventListener("click", () => {
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
    });
}

export default function initSeasonView(options = {}) {
    const selectors = options.tableSelectors || [
        "#season-games-table",
        "#season-roster-table",
        "#season-stats-table",
    ];

    const tables = selectors
        .map((selector) => document.querySelector(selector))
        .filter(Boolean)
        .map((table) => initTable(table));

    document.querySelectorAll("[data-season-blog]").forEach((section) => {
        setupBlogClicks(section);
    });

    return { tables };
}

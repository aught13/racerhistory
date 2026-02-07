import initBlogInteractions from './blog-interactions.mjs';

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

function safeDivide(numerator, denominator) {
    const num = Number(numerator);
    const den = Number(denominator);
    if (!Number.isFinite(num) || !Number.isFinite(den) || den <= 0) {
        return null;
    }
    return num / den;
}

function formatPercent(value) {
    if (!Number.isFinite(value)) {
        return "—";
    }
    return `${(value * 100).toFixed(1)}%`;
}

function formatInteger(value) {
    return Number.isFinite(value) ? String(value) : "—";
}

function createAdvancedRow(entry, hasThreePointShots) {
    const fgm = Number(entry.FGM) || 0;
    const fga = Number(entry.FGA) || 0;
    const tpm = Number(entry.TPM) || 0;
    const tpa = Number(entry.TPA) || 0;
    const ftm = Number(entry.FTM) || 0;
    const fta = Number(entry.FTA) || 0;
    const pts = Number(entry.PTS) || 0;
    const twoPm = Math.max(fgm - tpm, 0);
    const twoPa = Math.max(fga - tpa, 0);
    const tsDenominator = 2 * (fga + 0.44 * fta);

    return {
        name: (entry.name ?? "").toString(),
        GP: Number(entry.GP) || 0,
        FGM: fgm,
        FGA: fga,
        TPM: tpm,
        TPA: tpa,
        FTM: ftm,
        FTA: fta,
        PTS: pts,
        twoPm,
        twoPa,
        fgPct: safeDivide(fgm, fga),
        twoPct: safeDivide(twoPm, twoPa),
        tpPct: safeDivide(tpm, tpa),
        ftPct: safeDivide(ftm, fta),
        tsPct: safeDivide(pts, tsDenominator),
        efgPct:
            hasThreePointShots && fga > 0
                ? safeDivide(twoPm + 1.5 * tpm, fga)
                : null,
    };
}

function buildAdvancedColumns(hasThreePointShots) {
    const columns = [
        { label: "Player", value: (row) => row.name || "—" },
        { label: "GP", value: (row) => formatInteger(row.GP) },
        { label: "FGM", value: (row) => formatInteger(row.FGM) },
        { label: "FGA", value: (row) => formatInteger(row.FGA) },
        { label: "FG%", value: (row) => formatPercent(row.fgPct) },
    ];

    if (hasThreePointShots) {
        columns.push(
            { label: "2PM", value: (row) => formatInteger(row.twoPm) },
            { label: "2PA", value: (row) => formatInteger(row.twoPa) },
            { label: "2P%", value: (row) => formatPercent(row.twoPct) },
            { label: "TPM", value: (row) => formatInteger(row.TPM) },
            { label: "TPA", value: (row) => formatInteger(row.TPA) },
            { label: "TP%", value: (row) => formatPercent(row.tpPct) },
        );
    }

    columns.push(
        { label: "FTM", value: (row) => formatInteger(row.FTM) },
        { label: "FTA", value: (row) => formatInteger(row.FTA) },
        { label: "FT%", value: (row) => formatPercent(row.ftPct) },
        { label: "TS%", value: (row) => formatPercent(row.tsPct) },
    );

    if (hasThreePointShots) {
        columns.push({
            label: "eFG%",
            value: (row) => formatPercent(row.efgPct),
        });
    }

    columns.push({ label: "PTS", value: (row) => formatInteger(row.PTS) });

    return columns;
}

function mountAdvancedShootingTable(panel) {
    if (!panel || panel.dataset.seasonAdvancedRendered === "true") {
        if (panel) {
            panel.dataset.seasonAdvancedRendered = "true";
        }
        return;
    }

    if (!panel.dataset.seasonAdvancedStats) {
        panel.dataset.seasonAdvancedRendered = "true";
        return;
    }

    const container = panel.querySelector(
        "[data-season-advanced-table-container]",
    );
    if (!container) {
        panel.dataset.seasonAdvancedRendered = "true";
        return;
    }

    let payload;
    try {
        payload = JSON.parse(panel.dataset.seasonAdvancedStats);
    } catch {
        container.innerHTML =
            '<p class="text-muted mb-0">Advanced shooting metrics could not be loaded.</p>';
        panel.dataset.seasonAdvancedRendered = "true";
        return;
    }

    const players = Array.isArray(payload.players) ? payload.players : [];
    if (!players.length) {
        container.innerHTML =
            '<p class="text-muted mb-0">Advanced shooting metrics are unavailable.</p>';
        panel.dataset.seasonAdvancedRendered = "true";
        return;
    }

    const sanitizedPlayers = players.map((entry) => ({
        name: entry.name ?? "",
        GP: entry.GP,
        FGM: entry.FGM,
        FGA: entry.FGA,
        TPM: entry.TPM,
        TPA: entry.TPA,
        FTM: entry.FTM,
        FTA: entry.FTA,
        PTS: entry.PTS,
    }));

    const hasThreePointShots = sanitizedPlayers.some(
        (row) => Number(row.TPA) > 0 || Number(row.TPM) > 0,
    );

    const decoratedPlayers = sanitizedPlayers.map((row) =>
        createAdvancedRow(row, hasThreePointShots),
    );

    let teamTotalsRow = null;
    if (payload.teamTotals && typeof payload.teamTotals === "object") {
        const totalsEntry = {
            name: payload.teamTotals.name ?? "",
            GP: payload.teamTotals.GP,
            FGM: payload.teamTotals.FGM,
            FGA: payload.teamTotals.FGA,
            TPM: payload.teamTotals.TPM,
            TPA: payload.teamTotals.TPA,
            FTM: payload.teamTotals.FTM,
            FTA: payload.teamTotals.FTA,
            PTS: payload.teamTotals.PTS,
        };
        teamTotalsRow = createAdvancedRow(totalsEntry, hasThreePointShots);
    }

    const columns = buildAdvancedColumns(hasThreePointShots);
    const table = document.createElement("table");
    table.className =
        "table table-striped table-bordered table-sm js-datatable";

    const thead = document.createElement("thead");
    const headerRow = document.createElement("tr");
    columns.forEach((column) => {
        const th = document.createElement("th");
        th.textContent = column.label;
        headerRow.appendChild(th);
    });
    thead.appendChild(headerRow);
    table.appendChild(thead);

    const tbody = document.createElement("tbody");
    decoratedPlayers.forEach((row) => {
        const tr = document.createElement("tr");
        columns.forEach((column) => {
            const td = document.createElement("td");
            td.textContent = column.value(row);
            tr.appendChild(td);
        });
        tbody.appendChild(tr);
    });
    table.appendChild(tbody);

    if (teamTotalsRow) {
        const tfoot = document.createElement("tfoot");
        const footerRow = document.createElement("tr");
        footerRow.classList.add("table-secondary", "fw-bold");
        columns.forEach((column) => {
            const td = document.createElement("td");
            td.textContent = column.value(teamTotalsRow);
            footerRow.appendChild(td);
        });
        tfoot.appendChild(footerRow);
        table.appendChild(tfoot);
    }

    container.innerHTML = "";
    container.appendChild(table);
    initTable(table);
    panel.dataset.seasonAdvancedRendered = "true";
}

function initSeasonStatsTabs(root) {
    if (!root) {
        return;
    }
    const tabButtons = Array.from(
        root.querySelectorAll("[data-season-stats-tab]"),
    );
    if (!tabButtons.length) {
        return;
    }

    const panels = Array.from(
        root.querySelectorAll("[data-season-stats-panel]"),
    );
    const advancedPanel = root.querySelector("[data-season-advanced-stats]");

    tabButtons.forEach((button) => {
        button.addEventListener("click", () => {
            const target = button.dataset.seasonStatsTab;
            if (!target) {
                return;
            }
            tabButtons.forEach((btn) => {
                const isActive = btn.dataset.seasonStatsTab === target;
                btn.classList.toggle("active", isActive);
                btn.setAttribute("aria-selected", isActive ? "true" : "false");
            });
            panels.forEach((panel) => {
                const matches = panel.dataset.seasonStatsPanel === target;
                panel.classList.toggle("active", matches);
                panel.classList.toggle("d-none", !matches);
            });
            if (target === "advanced" && advancedPanel) {
                mountAdvancedShootingTable(advancedPanel);
            }
        });
    });
}

function setupImageGallery(root) {
    if (!root) {
        return;
    }
    const gallery = root.querySelector("[data-season-image-gallery]");
    const modal = root.querySelector("[data-season-image-modal]");
    if (!gallery || !modal) {
        return;
    }

    const closeBtn = modal.querySelector("[data-modal-close]");
    const modalImg = modal.querySelector("[data-modal-image-fallback]");
    const modalWebp = modal.querySelector("[data-modal-image-webp]");
    if (!modalImg) {
        return;
    }

    // Close modal
    function closeModal() {
        modal.removeAttribute("data-modal-open");
    }

    closeBtn?.addEventListener("click", closeModal);

    // Click outside to close
    modal.addEventListener("click", (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    // Escape key to close
    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && modal.hasAttribute("data-modal-open")) {
            closeModal();
        }
    });

    // Image click handler
    gallery.addEventListener("click", (event) => {
        const img = event.target.closest(".season-photo-thumb-img");
        if (!img) {
            return;
        }

        const imageId = img.dataset.imageId;
        const filename = img.dataset.imageFilename;
        if (!imageId) {
            return;
        }

        // Set WebP source
        if (modalWebp) {
            modalWebp.srcset = `/images/serve/${imageId}?format=webp`;
        }

        // Set fallback JPG
        modalImg.src = `/images/serve/${imageId}`;
        modalImg.alt = filename || "";

        // Show modal
        modal.setAttribute("data-modal-open", "true");
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

    root.querySelectorAll("[data-season-stats-tabs]").forEach((section) => {
        initSeasonStatsTabs(section);
    });

    setupImageGallery(root);
    initBlogInteractions({ root });

    return { tables };
}

/* global Element */

import initBlogInteractions from "./blog-interactions.mjs";

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
    if (!table || table.dataset.gameTableInitScheduled === "true") {
        return null;
    }
    table.dataset.gameTableInitScheduled = "true";
    window.setTimeout(() => {
        table.dataset.gameTableInitScheduled = "false";
        initTable(table);
    }, HEADER_CHECK_DELAY);
    return null;
}

function initTable(table, options = DEFAULT_TABLE_OPTIONS) {
    const $ = window.$;
    if (!table) {
        return null;
    }
    if (!hasTableHeaders(table)) {
        return scheduleTableInit(table);
    }
    if (!$ || !$.fn || !$.fn.dataTable) {
        return null;
    }

    if ($.fn.dataTable.isDataTable(table)) {
        try {
            $(table).DataTable().destroy();
        } catch {
            // ignore
        }
    }

    return $(table).DataTable(options);
}

function getGameTableOptions(table) {
    const headers = Array.from(table.querySelectorAll("thead th"), (header) =>
        header.textContent.trim(),
    );
    const starterIndex = headers.indexOf("GS");
    const pointsIndex = headers.indexOf(
        table.id.includes("-advanced-") ? "PProd" : "PTS",
    );
    const order = [];

    if (starterIndex >= 0) {
        order.push([starterIndex, "desc"]);
    }
    if (pointsIndex >= 0) {
        order.push([pointsIndex, "desc"]);
    }

    return { ...DEFAULT_TABLE_OPTIONS, order };
}

function setupGameBoxScoreTabs(root) {
    const sections = [];
    if (root.matches?.("[data-game-box-score-tabs]")) {
        sections.push(root);
    }
    sections.push(...root.querySelectorAll("[data-game-box-score-tabs]"));

    sections.forEach((section) => {
        const tabs = Array.from(
            section.querySelectorAll("[data-game-stats-tab]"),
        );
        const panels = Array.from(
            section.querySelectorAll("[data-game-stats-panel]"),
        );
        tabs.forEach((tab) => {
            if (tab.dataset.gameStatsTabBound === "true") {
                return;
            }

            tab.dataset.gameStatsTabBound = "true";
            tab.addEventListener("click", () => {
                const activeTab = tab.dataset.gameStatsTab;
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
                panels.forEach((panel) => {
                    const active = panel.dataset.gameStatsPanel === activeTab;
                    panel.classList.toggle("active", active);
                    panel.classList.toggle("show", active);
                    panel.classList.toggle("d-none", !active);
                });

                if (activeTab !== "advanced") {
                    return;
                }

                const activePanel = panels.find(
                    (panel) => panel.dataset.gameStatsPanel === activeTab,
                );
                activePanel
                    ?.querySelectorAll("table.js-datatable")
                    .forEach((table) => {
                        if (
                            table.dataset.gameAdvancedTableInitialized ===
                            "true"
                        ) {
                            return;
                        }

                        if (initTable(table, getGameTableOptions(table))) {
                            table.dataset.gameAdvancedTableInitialized = "true";
                        }
                    });
            });
        });
    });
}

function navigateTo(url) {
    if (!url) {
        return;
    }

    if (window.Turbo && typeof window.Turbo.visit === "function") {
        window.Turbo.visit(url);
        return;
    }

    try {
        if (window.location && typeof window.location.assign === "function") {
            window.location.assign(url);
            return;
        }
    } catch {
        // Fall back to href assignment below.
    }

    try {
        window.location.href = url;
    } catch {
        // Ignore navigation errors in non-browser test environments.
    }
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
        if (!slug) {
            return;
        }
        const container = item.closest("turbo-frame");
        const viewFrame = container?.querySelector(
            "turbo-frame[data-view-frame]",
        );
        if (!viewFrame) {
            navigateTo(`/blog/${slug}`);
            return;
        }
        if (window.Turbo && typeof window.Turbo.visit === "function") {
            window.Turbo.visit(`/blog/${slug}`, { frame: viewFrame.id });
        } else {
            navigateTo(`/blog/${slug}`);
        }
    });
}

function setupImageGallery(root) {
    if (!root) {
        return;
    }
    const gallery = root.querySelector("[data-game-image-gallery]");
    const modal = root.querySelector("[data-game-image-modal]");
    if (!gallery || !modal) {
        return;
    }
    if (gallery.dataset.gameImageBound === "true") {
        return;
    }

    const modalImg = modal.querySelector("[data-modal-image-fallback]");
    const modalWebp = modal.querySelector("[data-modal-image-webp]");
    const modalCredit = modal.querySelector("[data-modal-photo-credit]");
    if (!modalImg) {
        return;
    }

    gallery.dataset.gameImageBound = "true";

    gallery.addEventListener("click", (event) => {
        const img = event.target.closest(".game-photo-thumb-img");
        if (!img) {
            return;
        }

        const imageTrigger = img.closest("[data-image-url]") || img;
        const imageUrl =
            imageTrigger.dataset.imageUrl || img.currentSrc || img.src;
        const filename =
            imageTrigger.dataset.imageFilename || img.dataset.imageFilename;
        if (!imageUrl) {
            return;
        }

        if (modalWebp) {
            modalWebp.removeAttribute("srcset");
        }

        modalImg.src = imageUrl;
        modalImg.alt = filename || "";

        const photoCredit = img.dataset.photoCredit || "";
        if (modalCredit) {
            modalCredit.textContent =
                imageTrigger.dataset.photoCredit || photoCredit;
            modalCredit.classList.toggle("d-none", !modalCredit.textContent);
        }
    });
}

export default function initGameView(options = {}) {
    const root = options.root || document;
    const selectors = options.tableSelectors || [
        "#game-team-stats-table",
        "#game-opponent-stats-table",
    ];

    const tables = selectors
        .map((selector) => root.querySelector(selector))
        .filter(Boolean)
        .map((table) => initTable(table, getGameTableOptions(table)));

    setupGameBoxScoreTabs(root);
    root.querySelectorAll("[data-game-blog]").forEach((section) => {
        setupBlogClicks(section);
    });

    setupImageGallery(root);
    initBlogInteractions({ root });

    return { tables };
}

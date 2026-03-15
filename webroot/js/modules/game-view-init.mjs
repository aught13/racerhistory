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

function setupImageGallery(root) {
    if (!root) {
        return;
    }
    const gallery = root.querySelector("[data-game-image-gallery]");
    const modal = root.querySelector("[data-game-image-modal]");
    if (!gallery || !modal) {
        return;
    }
    if (modal.dataset.gameImageBound === "true") {
        return;
    }
    modal.dataset.gameImageBound = "true";

    const closeBtn = modal.querySelector("[data-modal-close]");
    const modalImg = modal.querySelector("[data-modal-image-fallback]");
    const modalWebp = modal.querySelector("[data-modal-image-webp]");
    if (!modalImg) {
        return;
    }

    function closeModal() {
        modal.removeAttribute("data-modal-open");
    }

    closeBtn?.addEventListener("click", closeModal);

    modal.addEventListener("click", (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && modal.hasAttribute("data-modal-open")) {
            closeModal();
        }
    });

    gallery.addEventListener("click", (event) => {
        const img = event.target.closest(".game-photo-thumb-img");
        if (!img) {
            return;
        }

        const imageId = img.dataset.imageId;
        const filename = img.dataset.imageFilename;
        if (!imageId) {
            return;
        }

        if (modalWebp) {
            modalWebp.srcset = `/images/serve/${imageId}?format=webp`;
        }

        modalImg.src = `/images/serve/${imageId}`;
        modalImg.alt = filename || "";

        modal.setAttribute("data-modal-open", "true");
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
        .map((table) => {
            const isPlayerTable =
                table.id === "game-team-stats-table" ||
                table.id === "game-opponent-stats-table";
            const options = isPlayerTable
                ? {
                      ...DEFAULT_TABLE_OPTIONS,
                      order: [
                          [2, "desc"],
                          [3, "desc"],
                      ],
                  }
                : DEFAULT_TABLE_OPTIONS;
            return initTable(table, options);
        });

    root.querySelectorAll("[data-game-blog]").forEach((section) => {
        setupBlogClicks(section);
    });

    setupImageGallery(root);
    initBlogInteractions({ root });

    return { tables };
}

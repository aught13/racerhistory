/* global Element */

const ITEM_SELECTOR = "[data-person-blog-popover]";
const ROOT_SELECTOR = "[data-person-blog-popovers]";
const POPOVER_CLASS = "person-blog-popover";
let activePopover = null;
let docBound = false;

function navigateTo(url) {
    if (!url) {
        return;
    }

    const testNavigate = window.__RH_NAVIGATE__;
    if (typeof testNavigate === "function") {
        testNavigate(url);
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

function destroyPopover() {
    if (!activePopover) {
        return;
    }
    const { popover } = activePopover;
    if (popover && popover.parentNode) {
        popover.parentNode.removeChild(popover);
    }
    activePopover = null;
}

function positionPopover(popover, trigger) {
    if (!popover || !trigger?.getBoundingClientRect) {
        return;
    }

    const rect = trigger.getBoundingClientRect();
    popover.style.position = "absolute";
    popover.style.minWidth = "260px";
    popover.style.maxWidth = "420px";
    popover.style.width = "320px";
    popover.style.zIndex = "1050";

    const padding = 8;
    const top = rect.bottom + window.scrollY + padding;
    const rawLeft =
        rect.left + window.scrollX + rect.width / 2 - popover.offsetWidth / 2;
    const left = Math.max(padding + window.scrollX, rawLeft);

    popover.style.top = `${top}px`;
    popover.style.left = `${left}px`;
}

function renderPopover(anchor, bodyHtml) {
    destroyPopover();

    const container = document.createElement("div");
    container.className = `${POPOVER_CLASS} card shadow-sm border bg-white p-3`;
    container.innerHTML = bodyHtml;
    document.body.appendChild(container);

    activePopover = { anchor, popover: container };
    positionPopover(container, anchor);
    return container;
}

function showLoading(anchor) {
    return renderPopover(
        anchor,
        '<div class="small text-muted">Loading story...</div>',
    );
}

function handleError(anchor) {
    renderPopover(
        anchor,
        '<p class="text-muted mb-0">Unable to load story preview.</p>',
    );
}

async function fetchPopover(anchor) {
    const url = anchor?.dataset?.personBlogPopoverUrl;
    if (!url) {
        return null;
    }

    const response = await fetch(url, {
        headers: { "X-Requested-With": "XMLHttpRequest" },
    });

    if (!response.ok) {
        throw new Error("Unable to fetch popover");
    }

    return response.text();
}

function openPopover(anchor) {
    if (!anchor) {
        return;
    }

    const url = anchor.dataset?.personBlogPopoverUrl;
    if (!url) {
        if (anchor.href) {
            navigateTo(anchor.href);
        }
        return;
    }

    showLoading(anchor);

    fetchPopover(anchor)
        .then((html) => {
            const safeHtml = typeof html === "string" ? html : "";
            renderPopover(
                anchor,
                safeHtml ||
                    '<p class="text-muted mb-0">No preview available.</p>',
            );
        })
        .catch(() => handleError(anchor));
}

function bindDocumentHandlers() {
    if (docBound) {
        return;
    }
    docBound = true;

    document.addEventListener(
        "click",
        (event) => {
            const target =
                event?.target instanceof Element ? event.target : null;
            const inPopover = target?.closest?.(`.${POPOVER_CLASS}`);
            const isTrigger = target?.closest?.(ITEM_SELECTOR);
            if (inPopover || isTrigger) {
                return;
            }
            destroyPopover();
        },
        true,
    );

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            destroyPopover();
        }
    });
}

function bindLink(link) {
    if (!link || link.dataset.personBlogPopoverBound === "true") {
        return;
    }

    link.dataset.personBlogPopoverBound = "true";
    link.addEventListener("click", (event) => {
        if (
            event.metaKey ||
            event.ctrlKey ||
            event.shiftKey ||
            event.button !== 0
        ) {
            return;
        }
        event.preventDefault();
        openPopover(link);
    });
}

export default function initPersonBlogPopovers(options = {}) {
    const root =
        options.root && options.root.querySelector ? options.root : document;
    const scope = root.querySelector(ROOT_SELECTOR) || root;
    const links = Array.from(scope.querySelectorAll(ITEM_SELECTOR));

    if (!links.length) {
        return { links: [] };
    }

    links.forEach(bindLink);
    bindDocumentHandlers();

    return { links };
}

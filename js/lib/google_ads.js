const GOOGLE_SLOT_SELECTOR = "section.rh-ad-slot--google";
const ADSENSE_SCRIPT_SELECTOR =
    'script[src*="pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"]';
const EMPTY_SLOT_CLASS = "rh-ad-slot--empty";

function isElement(value) {
    return (
        typeof globalThis !== "undefined" &&
        typeof globalThis.Element === "function" &&
        value instanceof globalThis.Element
    );
}

function ensureGoogleQueue() {
    if (typeof window === "undefined") {
        return null;
    }

    if (typeof window.adsbygoogle === "undefined") {
        window.adsbygoogle = [];
    }

    if (typeof window.adsbygoogle?.push !== "function") {
        return null;
    }

    return window.adsbygoogle;
}

export function removeDuplicateGoogleAdScripts(root = document) {
    if (!root || typeof root.querySelectorAll !== "function") {
        return 0;
    }

    const scripts = Array.from(root.querySelectorAll(ADSENSE_SCRIPT_SELECTOR));
    scripts.slice(1).forEach((script) => script.remove());

    return Math.max(0, scripts.length - 1);
}

export function installGoogleAdScriptCleanup() {
    if (typeof window === "undefined" || typeof document === "undefined") {
        return;
    }

    if (window.__RH_ADSENSE_SCRIPT_CLEANUP__) {
        return;
    }

    window.__RH_ADSENSE_SCRIPT_CLEANUP__ = true;
    removeDuplicateGoogleAdScripts(document);
    document.addEventListener("turbo:load", () => {
        removeDuplicateGoogleAdScripts(document);
    });
}

function getGoogleTagSlotId(section) {
    if (!isElement(section)) {
        return null;
    }

    const explicit = section.dataset?.googleTagSlotId;
    if (typeof explicit === "string" && explicit.trim() !== "") {
        return explicit.trim();
    }

    return null;
}

function queueGoogleTagDisplay(section) {
    if (!isElement(section) || typeof window === "undefined") {
        return false;
    }

    const slotId = getGoogleTagSlotId(section);
    if (
        !slotId ||
        !window.googletag ||
        typeof window.googletag.cmd?.push !== "function"
    ) {
        return false;
    }

    if (section.dataset.rhGoogleTagQueued === "1") {
        return false;
    }

    window.googletag.cmd.push(function () {
        if (typeof window.googletag.display === "function") {
            window.googletag.display(slotId);
        }
    });

    section.dataset.rhGoogleTagQueued = "1";
    return true;
}

export function syncGoogleAdSlotState(section, adElement) {
    if (!isElement(section) || !isElement(adElement)) {
        return false;
    }

    const isRendered =
        adElement.getAttribute("data-adsbygoogle-status") === "done";
    const isUnfilled =
        isRendered && adElement.getAttribute("data-ad-status") === "unfilled";
    section.classList.toggle(EMPTY_SLOT_CLASS, isUnfilled);
    section.setAttribute("data-rh-ad-initialized", "1");

    if (isUnfilled) {
        adElement.style.display = "none";
        adElement.style.width = "0";
        adElement.style.height = "0";
        adElement.style.minHeight = "0";
        adElement.style.maxHeight = "0";
        adElement.style.overflow = "hidden";
        return true;
    }

    adElement.style.display = "";
    adElement.style.width = "";
    adElement.style.height = "";
    adElement.style.minHeight = "";
    adElement.style.maxHeight = "";
    adElement.style.overflow = "";

    return false;
}

function disconnectObserver(section) {
    const observer = section?.__rhGoogleAdObserver;
    if (!observer || typeof observer.disconnect !== "function") {
        return;
    }

    observer.disconnect();
    delete section.__rhGoogleAdObserver;
}

function disconnectSizeObserver(section) {
    const observer = section?.__rhGoogleAdSizeObserver;
    if (!observer || typeof observer.disconnect !== "function") {
        return;
    }

    observer.disconnect();
    delete section.__rhGoogleAdSizeObserver;
}

function hasGoogleAdLayout(section, adElement) {
    if (
        typeof document === "undefined" ||
        typeof document.documentElement?.getClientRects !== "function" ||
        document.documentElement.getClientRects().length === 0
    ) {
        return true;
    }

    const container =
        section.querySelector(".rh-ad-slot__inner") ||
        adElement.parentElement ||
        section;
    let current = container;

    while (current) {
        const style = globalThis.getComputedStyle?.(current);
        if (style?.display === "none" || style?.visibility === "hidden") {
            return false;
        }

        current = current.parentElement;
    }

    return container.getBoundingClientRect().width > 0;
}

function waitForGoogleAdLayout(section, adElement) {
    if (section.__rhGoogleAdSizeObserver) {
        return;
    }

    const retry = () => {
        if (!isElement(section) || section.dataset.rhAdInitialized === "1") {
            disconnectSizeObserver(section);
            return;
        }

        if (!hasGoogleAdLayout(section, adElement)) {
            return;
        }

        disconnectSizeObserver(section);
        initGoogleAdSlotSection(section);
    };

    if (typeof globalThis.ResizeObserver === "function") {
        const observer = new globalThis.ResizeObserver(retry);
        observer.observe(section);
        observer.observe(
            section.querySelector(".rh-ad-slot__inner") || section,
        );
        section.__rhGoogleAdSizeObserver = observer;
    }

    if (typeof globalThis.requestAnimationFrame === "function") {
        globalThis.requestAnimationFrame(retry);
    } else {
        globalThis.setTimeout(retry, 0);
    }
}

export function destroyGoogleAdSlotSection(section) {
    if (!isElement(section)) {
        return;
    }

    disconnectObserver(section);
    disconnectSizeObserver(section);
    section.classList.remove(EMPTY_SLOT_CLASS);
    section.removeAttribute("data-rh-ad-initialized");
    delete section.dataset.rhGoogleTagQueued;
}

export function initGoogleAdSlotSection(section) {
    if (!isElement(section)) {
        return false;
    }

    removeDuplicateGoogleAdScripts();

    if (section.dataset.rhAdInitialized === "1") {
        return false;
    }

    if (section.__rhGoogleAdSizeObserver) {
        return false;
    }

    if (queueGoogleTagDisplay(section)) {
        section.setAttribute("data-rh-ad-initialized", "1");
        return true;
    }

    const adElement = section.querySelector(".adsbygoogle, ins.adsbygoogle");

    if (!adElement) {
        section.setAttribute("data-rh-ad-initialized", "1");
        return true;
    }

    if (!hasGoogleAdLayout(section, adElement)) {
        waitForGoogleAdLayout(section, adElement);
        return false;
    }

    const queue = ensureGoogleQueue();
    const wasRendered =
        adElement.getAttribute("data-adsbygoogle-status") === "done";

    if (queue && !wasRendered) {
        queue.push({});
    }

    section.setAttribute("data-rh-ad-initialized", "1");

    const canObserveStatus =
        typeof globalThis !== "undefined" &&
        typeof globalThis.MutationObserver === "function";

    if (canObserveStatus) {
        disconnectObserver(section);
        const observer = new globalThis.MutationObserver(() => {
            syncGoogleAdSlotState(section, adElement);
        });

        observer.observe(adElement, {
            attributes: true,
            attributeFilter: ["data-ad-status", "data-adsbygoogle-status"],
        });

        section.__rhGoogleAdObserver = observer;
    }

    syncGoogleAdSlotState(section, adElement);

    return true;
}

export function initGoogleAdSlots(root = document) {
    if (!root || typeof root.querySelectorAll !== "function") {
        return [];
    }

    const sections = Array.from(root.querySelectorAll(GOOGLE_SLOT_SELECTOR));
    const initialized = [];

    sections.forEach((section) => {
        if (!initGoogleAdSlotSection(section)) {
            return;
        }

        initialized.push(section);
    });

    return initialized;
}

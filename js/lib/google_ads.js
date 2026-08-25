const GOOGLE_SLOT_SELECTOR = "section.rh-ad-slot--google";
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

function getGoogleTagSlotId(section) {
    if (!isElement(section)) {
        return null;
    }

    const explicit = section.dataset?.googleSlotId || section.dataset?.adSlot;
    if (typeof explicit === "string" && explicit.trim() !== "") {
        return explicit.trim();
    }

    const directId = section.id || section.querySelector("[id]")?.id;
    if (typeof directId === "string" && directId.trim() !== "") {
        return directId.trim();
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

    const isUnfilled = adElement.getAttribute("data-ad-status") === "unfilled";
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

export function destroyGoogleAdSlotSection(section) {
    if (!isElement(section)) {
        return;
    }

    disconnectObserver(section);
    section.classList.remove(EMPTY_SLOT_CLASS);
    section.removeAttribute("data-rh-ad-initialized");
    delete section.dataset.rhGoogleTagQueued;
}

export function initGoogleAdSlotSection(section) {
    if (!isElement(section)) {
        return false;
    }

    if (section.dataset.rhAdInitialized === "1") {
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
            attributeFilter: ["data-ad-status"],
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

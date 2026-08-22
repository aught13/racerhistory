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

export function initGoogleAdSlots(root = document) {
    if (!root || typeof root.querySelectorAll !== "function") {
        return [];
    }

    const queue = ensureGoogleQueue();
    const sections = Array.from(root.querySelectorAll(GOOGLE_SLOT_SELECTOR));
    const initialized = [];

    sections.forEach((section) => {
        if (section.dataset.rhAdInitialized === "1") {
            return;
        }

        const adElement = section.querySelector(
            ".adsbygoogle, ins.adsbygoogle",
        );

        if (!adElement) {
            section.setAttribute("data-rh-ad-initialized", "1");
            return;
        }

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
        initialized.push(section);
    });

    return initialized;
}

const doc = globalThis.document;
const El = globalThis.Element;
const css = globalThis.CSS;

const TAB_SELECTOR = "[data-person-game-log-tab]";

function getFrame(frameId, scope) {
    if (!frameId) {
        return null;
    }

    const root = scope && scope.querySelector ? scope : doc;

    if (typeof css !== "undefined" && css?.escape) {
        const escaped = css.escape(frameId);
        const frame = root.querySelector(`#${escaped}`);
        if (frame) {
            return frame;
        }
    }

    return doc.getElementById(frameId);
}

function hydrateFrame(frame) {
    if (!frame) {
        return false;
    }

    const src =
        frame.dataset?.personGameLogSrc ||
        frame.getAttribute("data-person-game-log-src");
    if (!src) {
        return false;
    }

    if (!frame.getAttribute("src")) {
        frame.setAttribute("src", src);
        return true;
    }

    return false;
}

function bindTab(tab, scope) {
    if (!tab || tab.dataset.personGameLogBound === "true") {
        return;
    }

    tab.dataset.personGameLogBound = "true";
    tab.addEventListener("click", (event) => {
        const current =
            event?.currentTarget instanceof El ? event.currentTarget : null;
        const frameId =
            current?.dataset?.personGameLogFrame ||
            current?.getAttribute("data-person-game-log-frame");
        const frame = getFrame(frameId, scope);
        hydrateFrame(frame);
    });
}

export default function initPersonGameLogTabs(options = {}) {
    const scope =
        options.root && options.root.querySelector ? options.root : doc;
    const tabs = Array.from(scope.querySelectorAll(TAB_SELECTOR));

    if (!tabs.length) {
        return { tabs: [] };
    }

    tabs.forEach((tab) => bindTab(tab, scope));

    const activeTab = tabs.find((tab) => tab.classList.contains("active"));
    if (activeTab) {
        const frameId =
            activeTab.dataset?.personGameLogFrame ||
            activeTab.getAttribute("data-person-game-log-frame");
        const frame = getFrame(frameId, scope);
        hydrateFrame(frame);
    }

    return { tabs };
}

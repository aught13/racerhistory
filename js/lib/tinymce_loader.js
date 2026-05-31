const TINYMCE_SRC = "/js/tinymce/tinymce.min.js?v=1";
const TINYMCE_REQUIRED_SELECTOR =
    '[data-controller~="blog-post-form"], [data-controller~="person-form"], [data-controller~="team-season-form"]';

let listenersBound = false;

function pageNeedsTinyMce() {
    return document.querySelector(TINYMCE_REQUIRED_SELECTOR) !== null;
}

function ensureTinyMceScript() {
    if (typeof window.tinymce !== "undefined") {
        return;
    }

    const existingScript = document.querySelector(
        'script[data-rh-tinymce="true"], script[src*="/js/tinymce/tinymce.min.js"]',
    );
    if (existingScript) {
        return;
    }

    const script = document.createElement("script");
    script.src = TINYMCE_SRC;
    script.async = true;
    script.dataset.rhTinymce = "true";
    document.head.appendChild(script);
}

function maybeLoadTinyMce() {
    if (pageNeedsTinyMce()) {
        ensureTinyMceScript();
    }
}

function bindListenersOnce() {
    if (listenersBound) {
        return;
    }

    document.addEventListener("DOMContentLoaded", maybeLoadTinyMce);
    document.addEventListener("turbo:load", maybeLoadTinyMce);
    listenersBound = true;
}

export function initTinyMceLoader() {
    if (typeof document === "undefined") {
        return;
    }

    bindListenersOnce();

    if (document.readyState !== "loading") {
        maybeLoadTinyMce();
    }
}

/* global HTMLImageElement, URL, document, window */

(function () {
    if (window.__rhImageRetryInit) {
        return;
    }
    window.__rhImageRetryInit = true;

    function isServeUrl(url) {
        if (!url) {
            return false;
        }
        try {
            const parsed = new URL(url, window.location.origin);
            return (
                parsed.pathname.indexOf("/images/serve/") === 0 ||
                parsed.pathname.indexOf("/img/storage/") === 0
            );
        } catch {
            return false;
        }
    }

    function bustUrl(url) {
        const parsed = new URL(url, window.location.origin);
        parsed.searchParams.set("_ts", String(Date.now()));

        return parsed.pathname + parsed.search;
    }

    function bustSrcset(srcset) {
        return srcset
            .split(",")
            .map(function (candidate) {
                const trimmed = candidate.trim();
                if (!trimmed) {
                    return trimmed;
                }

                const splitAt = trimmed.search(/\s/);
                const url =
                    splitAt === -1 ? trimmed : trimmed.slice(0, splitAt);
                const descriptor = splitAt === -1 ? "" : trimmed.slice(splitAt);

                if (!isServeUrl(url)) {
                    return trimmed;
                }

                return bustUrl(url) + descriptor;
            })
            .join(", ");
    }

    function retryBrokenImage(img) {
        if (!(img instanceof HTMLImageElement)) {
            return;
        }
        if (img.dataset.rhRetryAttempted === "1") {
            return;
        }

        const current = img.currentSrc || img.getAttribute("src") || "";
        if (!isServeUrl(current)) {
            return;
        }

        img.dataset.rhRetryAttempted = "1";

        const picture = img.closest("picture");
        if (picture) {
            picture.querySelectorAll("source").forEach(function (sourceEl) {
                const srcset = sourceEl.getAttribute("srcset");
                if (!srcset) {
                    return;
                }
                sourceEl.setAttribute("srcset", bustSrcset(srcset));
            });
        }

        const baseSrc = img.getAttribute("src") || current;
        img.setAttribute("src", bustUrl(baseSrc));
    }

    function retryAlreadyBroken() {
        document.querySelectorAll("img").forEach(function (img) {
            if (img.complete && img.naturalWidth === 0) {
                retryBrokenImage(img);
            }
        });
    }

    document.addEventListener(
        "error",
        function (event) {
            retryBrokenImage(event.target);
        },
        true,
    );

    document.addEventListener("DOMContentLoaded", retryAlreadyBroken);
    document.addEventListener("turbo:load", retryAlreadyBroken);
})();

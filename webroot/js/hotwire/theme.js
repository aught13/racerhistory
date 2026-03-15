/* global module */

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) {
        return parts.pop().split(";").shift();
    }
    return "";
}

function setCookie(name, value, days) {
    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value}; Max-Age=${maxAge}; Path=/; SameSite=Lax`;
}

export function getThemePreference() {
    const value = getCookie("theme");
    if (value === "light" || value === "dark") {
        return value;
    }
    return "system";
}

export function applyTheme(preference) {
    // Clear any previous system listener
    if (
        window.__rh_theme_mq &&
        typeof window.__rh_theme_mq.removeEventListener === "function"
    ) {
        try {
            window.__rh_theme_mq.removeEventListener(
                "change",
                window.__rh_theme_mq_listener,
            );
        } catch {
            // ignore
        }
        window.__rh_theme_mq = null;
        window.__rh_theme_mq_listener = null;
    }

    if (preference === "light" || preference === "dark") {
        // Explicit preference
        document.documentElement.dataset.theme = preference;
        delete document.documentElement.dataset.themeSource;
    } else {
        // 'system' - detect and apply current OS preference
        const mq =
            window.matchMedia &&
            window.matchMedia("(prefers-color-scheme: dark)");
        const applySystem = (ev) => {
            const dark =
                ev && typeof ev.matches === "boolean"
                    ? ev.matches
                    : mq
                      ? mq.matches
                      : false;
            document.documentElement.dataset.theme = dark ? "dark" : "light";
            document.documentElement.dataset.themeSource = "system";
        };

        applySystem();

        // Keep reference to listener so it can be removed when user switches away from system
        if (mq && typeof mq.addEventListener === "function") {
            window.__rh_theme_mq = mq;
            window.__rh_theme_mq_listener = applySystem;
            mq.addEventListener("change", applySystem);
        }
    }
}

export function setThemePreference(preference) {
    if (preference === "light" || preference === "dark") {
        setCookie("theme", preference, 365);
    } else {
        // 'system' clears cookie.
        setCookie("theme", "", 1);
    }

    applyTheme(preference);
}

export function initThemeFromCookie() {
    applyTheme(getThemePreference());
}

// CommonJS fallback for test environments that `require()` modules.
/* istanbul ignore next */
if (typeof module !== "undefined" && typeof module.exports !== "undefined") {
    module.exports = {
        getThemePreference,
        applyTheme,
        setThemePreference,
        initThemeFromCookie,
    };
}

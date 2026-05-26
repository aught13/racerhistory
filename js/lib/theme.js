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
            // Ignore media query detach errors.
        }

        window.__rh_theme_mq = null;
        window.__rh_theme_mq_listener = null;
    }

    if (preference === "light" || preference === "dark") {
        document.documentElement.dataset.theme = preference;
        delete document.documentElement.dataset.themeSource;

        return;
    }

    const mq =
        window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)");
    const applySystem = (event) => {
        const dark =
            event && typeof event.matches === "boolean"
                ? event.matches
                : mq
                  ? mq.matches
                  : false;
        document.documentElement.dataset.theme = dark ? "dark" : "light";
        document.documentElement.dataset.themeSource = "system";
    };

    applySystem();

    if (mq && typeof mq.addEventListener === "function") {
        window.__rh_theme_mq = mq;
        window.__rh_theme_mq_listener = applySystem;
        mq.addEventListener("change", applySystem);
    }
}

export function setThemePreference(preference) {
    if (preference === "light" || preference === "dark") {
        setCookie("theme", preference, 365);
    } else {
        setCookie("theme", "", 1);
    }

    applyTheme(preference);
}

export function initThemeFromCookie() {
    applyTheme(getThemePreference());
}

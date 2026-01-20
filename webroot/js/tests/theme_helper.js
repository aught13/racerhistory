// Minimal CommonJS helper duplicating theme behavior for tests
/* eslint-env node */
/* global module */
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(";").shift();
    return "";
}

function setCookie(name, value, days) {
    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value}; Max-Age=${maxAge}; Path=/; SameSite=Lax`;
}

function getThemePreference() {
    const value = getCookie("theme");
    if (value === "light" || value === "dark") return value;
    return "system";
}

function applyTheme(preference) {
    if (preference === "light" || preference === "dark") {
        document.documentElement.dataset.theme = preference;
        delete document.documentElement.dataset.themeSource;
        return;
    }

    const mq =
        window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)");
    const dark = mq ? mq.matches : false;
    document.documentElement.dataset.theme = dark ? "dark" : "light";
    document.documentElement.dataset.themeSource = "system";
}

function setThemePreference(pref) {
    if (pref === "light" || pref === "dark") {
        setCookie("theme", pref, 365);
    } else {
        setCookie("theme", "", 1);
    }
    applyTheme(pref);
}

module.exports = {
    getCookie,
    setCookie,
    getThemePreference,
    applyTheme,
    setThemePreference,
};

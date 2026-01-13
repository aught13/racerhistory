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
    if (preference === "light" || preference === "dark") {
        document.documentElement.dataset.theme = preference;
    } else {
        // system
        delete document.documentElement.dataset.theme;
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

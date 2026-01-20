/* eslint-env jest, node */
let theme;

// Helper to mock matchMedia
function mockMatchMedia(matches) {
    Object.defineProperty(window, "matchMedia", {
        writable: true,
        value: (query) => ({
            matches: !!matches,
            media: query,
            addEventListener: () => {},
            removeEventListener: () => {},
        }),
    });
}

// Try to dynamically import the ESM source; fall back to the CommonJS test helper.
beforeAll(async () => {
    try {
        // Use dynamic import so Jest can load ESM when configured
        const mod = await import("../hotwire/theme.js");
        theme = mod;
    } catch {
        // Fall back to the local CommonJS helper
        theme = require("./theme_helper.js");
    }
});

describe("theme toggle", () => {
    beforeEach(() => {
        // Clear cookie and dataset
        document.cookie = "theme=; Max-Age=0; Path=/";
        delete document.documentElement.dataset.theme;
        delete document.documentElement.dataset.themeSource;
        // clear any globals
        window.__rh_theme_mq = null;
        window.__rh_theme_mq_listener = null;
    });

    test("explicit light and dark preferences set dataset.theme", () => {
        theme.setThemePreference("light");
        expect(document.documentElement.dataset.theme).toBe("light");
        theme.setThemePreference("dark");
        expect(document.documentElement.dataset.theme).toBe("dark");
    });

    test("system preference applies dark when matchMedia matches", () => {
        mockMatchMedia(true);
        theme.setThemePreference("system");
        // when system, theme is set to dark or light string and themeSource present
        expect(document.documentElement.dataset.themeSource).toBe("system");
        expect(["dark", "light"]).toContain(
            document.documentElement.dataset.theme,
        );
        expect(document.documentElement.dataset.theme).toBe("dark");
    });

    test("system preference applies light when matchMedia does not match", () => {
        mockMatchMedia(false);
        theme.setThemePreference("system");
        expect(document.documentElement.dataset.themeSource).toBe("system");
        expect(document.documentElement.dataset.theme).toBe("light");
    });

    test("getThemePreference reads cookie or returns system when unset", () => {
        theme.setThemePreference("light");
        expect(theme.getThemePreference()).toBe("light");
        theme.setThemePreference("system");
        // cookie cleared => system
        expect(theme.getThemePreference()).toBe("system");
    });
});

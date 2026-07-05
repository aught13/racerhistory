/* global afterEach, beforeEach, describe, expect, test */

import {
    initPasswordToggle,
    initPasswordToggles,
} from "../lib/password_toggle.js";

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function buildDOM(pairs) {
    document.body.innerHTML = pairs
        .map(
            ([btnId, inputId]) => `
            <input type="password" id="${inputId}">
            <button type="button" id="${btnId}"><span class="bi bi-eye"></span></button>
        `,
        )
        .join("");
}

// ---------------------------------------------------------------------------
// initPasswordToggle
// ---------------------------------------------------------------------------

describe("initPasswordToggle", () => {
    beforeEach(() => {
        buildDOM([["toggle-pw", "pw"]]);
    });

    afterEach(() => {
        document.body.innerHTML = "";
    });

    test("toggles input type from password to text on first click", () => {
        initPasswordToggle("toggle-pw", "pw");
        const input = document.getElementById("pw");
        expect(input.type).toBe("password");
        document.getElementById("toggle-pw").click();
        expect(input.type).toBe("text");
    });

    test("toggles back to password on second click", () => {
        initPasswordToggle("toggle-pw", "pw");
        const btn = document.getElementById("toggle-pw");
        btn.click();
        btn.click();
        expect(document.getElementById("pw").type).toBe("password");
    });

    test("updates button icon to bi-eye-slash when showing password", () => {
        initPasswordToggle("toggle-pw", "pw");
        document.getElementById("toggle-pw").click();
        expect(document.getElementById("toggle-pw").innerHTML).toContain(
            "bi-eye-slash",
        );
    });

    test("restores button icon to bi-eye when hiding password", () => {
        initPasswordToggle("toggle-pw", "pw");
        const btn = document.getElementById("toggle-pw");
        btn.click();
        btn.click();
        expect(btn.innerHTML).toContain("bi-eye");
        expect(btn.innerHTML).not.toContain("bi-eye-slash");
    });

    test("does not throw when button element is missing", () => {
        expect(() => initPasswordToggle("nonexistent-btn", "pw")).not.toThrow();
    });

    test("does not throw when input element is missing", () => {
        expect(() =>
            initPasswordToggle("toggle-pw", "nonexistent-input"),
        ).not.toThrow();
    });

    test("replaces the button node to avoid duplicate listeners on turbo:load re-init", () => {
        initPasswordToggle("toggle-pw", "pw");
        // Simulate turbo:load re-init
        initPasswordToggle("toggle-pw", "pw");
        // If listeners were stacked, two clicks would be needed to toggle once
        document.getElementById("toggle-pw").click();
        expect(document.getElementById("pw").type).toBe("text");
    });
});

// ---------------------------------------------------------------------------
// initPasswordToggles (multi-field)
// ---------------------------------------------------------------------------

describe("initPasswordToggles", () => {
    beforeEach(() => {
        buildDOM([
            ["toggle-current", "current-password"],
            ["toggle-new", "new-password"],
            ["toggle-confirm", "confirm-password"],
        ]);
    });

    afterEach(() => {
        document.body.innerHTML = "";
    });

    test("wires up all provided pairs", () => {
        initPasswordToggles([
            ["toggle-current", "current-password"],
            ["toggle-new", "new-password"],
            ["toggle-confirm", "confirm-password"],
        ]);

        document.getElementById("toggle-current").click();
        document.getElementById("toggle-new").click();

        expect(document.getElementById("current-password").type).toBe("text");
        expect(document.getElementById("new-password").type).toBe("text");
        // confirm was not clicked — should remain password
        expect(document.getElementById("confirm-password").type).toBe(
            "password",
        );
    });

    test("handles empty pairs array without error", () => {
        expect(() => initPasswordToggles([])).not.toThrow();
    });
});
